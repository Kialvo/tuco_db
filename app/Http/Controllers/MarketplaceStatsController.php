<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\TokenPurchase;
use App\Models\TokenTransaction;
use App\Support\MarketplaceStats;
use App\Support\Statistics;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Marketplace Stats — the guest-buyer side of the product.
 *
 * Two pages, both read-only:
 *   • growth()  — acquisition, activation and engagement (Growth first: the
 *                 marketplace is in private beta, so "is anyone using it"
 *                 outranks "is it profitable")
 *   • revenue() — token revenue, the prepaid ledger, and order economics
 *
 * Scope: `users.role = 'guest'`. FOUR roles exist (admin, editor, publisher,
 * guest) and OrderController carries no internal role check — gating is at
 * route level only, so admin and test orders genuinely do sit in `orders`.
 * Every query below therefore joins `users` and filters explicitly; none of
 * them may assume a table is guest-only.
 *
 * NO DATE-RANGE PICKER, deliberately. The Link-Building pages share one picker
 * because every figure on them comes from one dated event (a publication's Live
 * Date). These pages do not have that: signups are dated by `users.created_at`,
 * orders by `submitted_at`, top-ups by `paid_at`, ledger movements by their own
 * `created_at` — and three KPIs (open carts, token liability, pipeline status)
 * are SNAPSHOTS with no defensible date column at all. One picker across those
 * would mean something different in every widget. Instead each trend shares one
 * month axis (MarketplaceStats::monthAxis) and each snapshot is labelled
 * "as of today" in the view.
 *
 * Money: 1 token = EUR 1, permanently (config/tokens.php), so token counts are
 * euro-denominated by construction and safe to sum. Cash (`amount_minor`) is
 * per-currency and is NEVER summed across currencies — every cash figure below
 * is either split by currency or expressed in tokens.
 */
class MarketplaceStatsController extends Controller
{
    /** Days a signup gets to place its first order before the cohort is judged. */
    private const FIRST_ORDER_WINDOW_DAYS = 30;

    /** Days a cart may sit untouched before it counts as abandoned. */
    private const CART_IDLE_DAYS = 7;

    /**
     * Order statuses in lifecycle order, from the orders-table enum.
     *
     * NOT Order::STATUSES, which omits `confirmed` and `approved`: the model
     * constants cover the statuses the app writes, the enum covers the statuses
     * the column can hold. A pipeline chart that silently dropped two of them
     * would not add up to the order count beside it.
     */
    private const ORDER_STATUS_ORDER = [
        'draft', 'submitted', 'confirmed', 'approved', 'in_progress', 'completed', 'cancelled',
    ];

    /**
     * Marketplace · Growth — acquisition, activation, engagement.
     *
     * Nine KPIs. A tenth (domain views / searches / view→cart rate) is specced
     * but parked: no page-view or search event is recorded anywhere in the app,
     * and this page is meant to prove whether that instrumentation is worth
     * building before it is built.
     */
    public function growth()
    {
        // ONE per-guest result set powers signups, activation, cohort conversion,
        // repeat rate and time-to-first-purchase. A second query per KPI could
        // disagree with this one about who is a buyer; four readings of one
        // result set cannot.
        $guests = DB::table('users as u')
            ->leftJoin('orders as o', function ($join) {
                $join->on('o.user_id', '=', 'u.id')->whereNotNull('o.submitted_at');
            })
            ->where('u.role', 'guest')
            ->groupBy('u.id', 'u.created_at')
            ->selectRaw('u.id as id, u.created_at as created_at')
            ->selectRaw('MIN(o.submitted_at) as first_submitted_at')
            ->selectRaw('COUNT(o.id) as submitted_orders')
            ->get();

        // First PAID top-up per guest — the other half of "activated": a buyer
        // who has funded a wallet but not yet spent it has still activated.
        $firstPaidAt = DB::table('token_purchases as p')
            ->join('users as u', fn ($join) => $join->on('u.id', '=', 'p.user_id')->where('u.role', '=', 'guest'))
            ->where('p.status', TokenPurchase::STATUS_PAID)
            ->whereNotNull('p.paid_at')
            ->groupBy('p.user_id')
            ->selectRaw('p.user_id as user_id, MIN(p.paid_at) as first_paid_at')
            ->pluck('first_paid_at', 'user_id');

        $signupsByMonth = [];      // 'Y-m' => count
        $activationsByMonth = [];  // 'Y-m' => count
        $cohorts = [];             // 'Y-m' => ['size' => n, 'converted' => n]
        $daysToFirstOrder = [];    // flat list, for the overall median
        $daysToFirstOrderByMonth = []; // activation month => [days, ...]
        $orderCountLabels = [];    // one bucket label per BUYER
        $buyers = 0;
        $repeatBuyers = 0;

        foreach ($guests as $guest) {
            $signedUp = Carbon::parse($guest->created_at);
            $signupMonth = $signedUp->format('Y-m');
            $signupsByMonth[$signupMonth] = ($signupsByMonth[$signupMonth] ?? 0) + 1;

            $firstOrder = $guest->first_submitted_at ? Carbon::parse($guest->first_submitted_at) : null;
            $firstPaid = isset($firstPaidAt[$guest->id]) ? Carbon::parse($firstPaidAt[$guest->id]) : null;

            // Activation = the EARLIER of the two, so a guest is counted once,
            // in the month they first did something that costs money.
            $activatedAt = $firstOrder;
            if ($firstPaid && (! $activatedAt || $firstPaid->lt($activatedAt))) {
                $activatedAt = $firstPaid;
            }

            if ($activatedAt) {
                $activationMonth = $activatedAt->format('Y-m');
                $activationsByMonth[$activationMonth] = ($activationsByMonth[$activationMonth] ?? 0) + 1;
            }

            // Cohort conversion is about ORDERS specifically, not activation:
            // the question is whether a signup reaches checkout, so a funded
            // wallet with no order has not converted.
            $cohorts[$signupMonth] ??= ['size' => 0, 'converted' => 0];
            $cohorts[$signupMonth]['size']++;

            if ($firstOrder) {
                // Negative diffs are impossible in practice but would poison a
                // median; floatDiffInDays is signed, so guard rather than trust.
                $days = max(0.0, $signedUp->floatDiffInDays($firstOrder, false));

                if ($days <= self::FIRST_ORDER_WINDOW_DAYS) {
                    $cohorts[$signupMonth]['converted']++;
                }

                $daysToFirstOrder[] = $days;
                $daysToFirstOrderByMonth[$firstOrder->format('Y-m')][] = $days;
            }

            $submittedOrders = (int) $guest->submitted_orders;
            if ($submittedOrders >= 1) {
                $buyers++;
                $orderCountLabels[] = MarketplaceStats::orderCountBucket($submittedOrders);
                if ($submittedOrders >= 2) {
                    $repeatBuyers++;
                }
            }
        }

        $orders = $this->ordersByMonth();
        $favorites = $this->favoritesByMonth();
        $activeBuyers = $this->activeBuyersByMonth();

        $axis = MarketplaceStats::monthAxis(MarketplaceStats::earliestMonth([
            $signupsByMonth, $activationsByMonth, $orders['submitted'], $favorites, $activeBuyers,
        ]));
        $months = array_values($axis);

        // Cohorts younger than the window are still moving. They are rendered —
        // hiding them would look like the marketplace stopped acquiring — but
        // flagged, so a half-grown 4% is never read as a collapse from 30%.
        $maturedThrough = MarketplaceStats::maturedThrough(self::FIRST_ORDER_WINDOW_DAYS);

        $cohortRows = [];
        foreach (array_keys($axis) as $ym) {
            $cohort = $cohorts[$ym] ?? ['size' => 0, 'converted' => 0];
            $cohortRows[] = [
                // Raw 'Y-m' alongside the label: the table sorts on this, because
                // 'Apr 2026' sorts before 'Aug 2026' as text and would put the
                // months in alphabetical rather than chronological order.
                'key' => $ym,
                'month' => $axis[$ym],
                'size' => $cohort['size'],
                'converted' => $cohort['converted'],
                // Null, never 0.0, for an empty cohort: "—" is honest, 0% is a
                // measurement nobody made.
                'rate' => Statistics::rate($cohort['converted'], $cohort['size']),
                'mature' => $maturedThrough !== null && $ym <= $maturedThrough,
            ];
        }

        return view('stats.marketplace.growth', [
            'months' => $months,

            // 1 · New guest signups
            'signupSeries' => MarketplaceStats::align($axis, $signupsByMonth),
            'totalSignups' => array_sum($signupsByMonth),

            // 2 · Activated buyers
            'activationSeries' => MarketplaceStats::align($axis, $activationsByMonth),
            'totalActivated' => array_sum($activationsByMonth),

            // 3 · Signup → first-order conversion
            'cohortRows' => $cohortRows,
            'cohortRateSeries' => array_column($cohortRows, 'rate'),
            'maturedThroughLabel' => $maturedThrough
                ? Carbon::createFromFormat('!Y-m', $maturedThrough)->format('M Y')
                : null,

            // 4 · Orders submitted (+ later-cancelled secondary line)
            'ordersSeries' => MarketplaceStats::align($axis, $orders['submitted']),
            'cancelledSeries' => MarketplaceStats::align($axis, $orders['cancelled']),
            'totalOrders' => array_sum($orders['submitted']),
            'totalCancelled' => array_sum($orders['cancelled']),

            // 5 · Active buyers per month
            'activeBuyerSeries' => MarketplaceStats::align($axis, $activeBuyers),

            // 6 · Repeat-buyer rate
            'buyers' => $buyers,
            'repeatBuyers' => $repeatBuyers,
            'repeatRate' => Statistics::rate($repeatBuyers, $buyers),
            'orderCountChart' => $this->toPie(
                MarketplaceStats::tally($orderCountLabels, MarketplaceStats::ORDER_COUNT_BUCKETS)
            ),

            // 7 · Open carts + abandonment
            ...$this->openCarts(),

            // 8 · Time to first purchase
            'medianDaysToFirstOrder' => Statistics::median($daysToFirstOrder),
            'medianDaysSeries' => MarketplaceStats::align(
                $axis,
                array_map(fn (array $days) => Statistics::median($days), $daysToFirstOrderByMonth),
                null // a month with no first-time buyer breaks the line rather than dropping to 0 days
            ),

            // 9 · Favorites → order conversion
            'favoriteSeries' => MarketplaceStats::align($axis, $favorites),
            ...$this->favoriteConversion(),
        ]);
    }

    /**
     * Marketplace · Revenue & Tokens — what the marketplace earns, what it owes,
     * and how orders move once placed. Ten KPIs, all computable from today's data.
     */
    public function revenue()
    {
        $purchases = $this->paidPurchasesByMonth();
        $attempts = $this->purchaseAttemptsByMonth();
        $ledger = $this->ledgerByMonth();
        $orderValues = $this->orderValuesByMonth();

        $axis = MarketplaceStats::monthAxis(MarketplaceStats::earliestMonth([
            $purchases['byMonth'], $attempts['byMonth'], $ledger['byMonth'], $orderValues['byMonth'],
        ]));
        $months = array_values($axis);

        // Token liability over time. The ledger stores MOVEMENTS, so the balance
        // at each month-end is the opening balance plus everything since — and
        // the opening balance is the pre-axis history, without which the line
        // would start at zero and understate what is owed for the whole chart.
        $firstAxisMonth = array_key_first($axis);
        $openingBalance = $firstAxisMonth === null ? 0 : (int) array_sum(array_filter(
            $ledger['net'],
            fn ($ym) => $ym < $firstAxisMonth,
            ARRAY_FILTER_USE_KEY
        ));

        $liabilitySeries = MarketplaceStats::cumulative(
            MarketplaceStats::align($axis, $ledger['net']),
            $openingBalance
        );

        $liability = $this->tokenLiability();

        // Cash revenue is per-currency and is never added up across currencies.
        // Each currency actually seen gets its own series; the peg-normalized
        // token line is the one figure that IS comparable across all of them.
        $revenueSeries = [];
        foreach ($purchases['currencies'] as $currency) {
            $revenueSeries[] = [
                'name' => $currency,
                'data' => array_map(
                    fn ($minor) => MarketplaceStats::fromMinor((int) $minor),
                    MarketplaceStats::align($axis, $purchases['revenueByCurrency'][$currency] ?? [])
                ),
            ];
        }

        // ARPPU — per currency, because its numerator is per-currency cash. A
        // guest who paid in both currencies is counted in BOTH denominators;
        // the alternative (one shared denominator) would divide euro revenue by
        // a headcount that includes people who never paid a euro.
        $arppuSeries = [];
        foreach ($purchases['currencies'] as $currency) {
            $data = [];
            foreach (array_keys($axis) as $ym) {
                $minor = (int) ($purchases['revenueByCurrency'][$currency][$ym] ?? 0);
                $payers = count($purchases['payersByCurrency'][$currency][$ym] ?? []);
                // Null, not 0: a month nobody paid in has no average to report.
                $data[] = $payers > 0 ? MarketplaceStats::fromMinor((int) round($minor / $payers)) : null;
            }
            $arppuSeries[] = ['name' => $currency, 'data' => $data];
        }

        $paid = $attempts['totals'][TokenPurchase::STATUS_PAID] ?? 0;
        $failed = $attempts['totals'][TokenPurchase::STATUS_FAILED] ?? 0;
        $expired = $attempts['totals']['expired'] ?? 0;

        return view('stats.marketplace.revenue', [
            'months' => $months,
            'currencies' => $purchases['currencies'],

            // 1 · Token revenue (split by currency) + tokens sold
            'revenueSeries' => $revenueSeries,
            'revenueTotals' => array_map(
                fn (array $byMonth) => MarketplaceStats::fromMinor((int) array_sum($byMonth)),
                $purchases['revenueByCurrency']
            ),
            'tokensSoldSeries' => MarketplaceStats::align($axis, $purchases['tokensByMonth']),
            'totalTokensSold' => (int) array_sum($purchases['tokensByMonth']),

            // 2 · Tokens purchased vs spent
            'creditsSeries' => MarketplaceStats::align($axis, $ledger['credits']),
            'debitsSeries' => MarketplaceStats::align($axis, $ledger['debits']),
            'totalCredits' => (int) array_sum($ledger['credits']),
            'totalDebits' => (int) array_sum($ledger['debits']),

            // 3 · Outstanding token liability
            'liabilityCached' => $liability['cached'],
            'liabilityDerived' => $liability['derived'],
            'liabilityDrift' => $liability['drift'],
            'liabilitySeries' => $liabilitySeries,

            // 4 · Package mix
            'packageRows' => $purchases['packageRows'],
            'packageChart' => $this->toPie(array_column($purchases['packageRows'], 'count', 'label')),

            // 5 · Average order value
            'aovSeries' => MarketplaceStats::align($axis, $orderValues['aovByMonth'], null),
            'averageOrderValue' => $orderValues['average'],
            'submittedOrderCount' => $orderValues['orderCount'],
            'articleTypeRows' => $orderValues['articleTypeRows'],

            // 6 · Revenue per paying buyer
            'arppuSeries' => $arppuSeries,

            // 7 · Payment success rate
            'paymentSuccessRate' => Statistics::rate($paid, $paid + $failed + $expired),
            'paymentAttemptRows' => $attempts['rows'],
            'paymentSuccessSeries' => MarketplaceStats::align($axis, $attempts['successRateByMonth'], null),

            // 8 · Refunds & adjustments
            'refundSeries' => MarketplaceStats::align($axis, $ledger['refunds']),
            'adjustmentSeries' => MarketplaceStats::align($axis, $ledger['adjustments']),
            'refundedPurchaseCount' => $attempts['totals'][TokenPurchase::STATUS_REFUNDED] ?? 0,
            'totalRefundTokens' => (int) array_sum($ledger['refunds']),
            'totalAdjustmentTokens' => (int) array_sum($ledger['adjustments']),

            // 9 · Bonus tokens granted
            'bonusSeries' => MarketplaceStats::align($axis, $purchases['bonusByMonth']),
            'totalBonusTokens' => (int) array_sum($purchases['bonusByMonth']),

            // 10 · Order pipeline health
            ...$this->orderPipeline(),
        ]);
    }

    /* ─────────────────────────── Growth builders ─────────────────────────── */

    /**
     * Orders submitted per month, plus how many of them were LATER CANCELLED.
     *
     * Submission is the counting event, so a cancelled order stays in the
     * submitted total and its cancellation is reported beside it. Netting them
     * out would quietly rewrite history every time an old order is cancelled.
     *
     * @return array{submitted: array<string, int>, cancelled: array<string, int>}
     */
    private function ordersByMonth(): array
    {
        $rows = $this->guestOrders()
            ->whereNotNull('o.submitted_at')
            ->groupBy('ym')
            ->selectRaw("DATE_FORMAT(o.submitted_at, '%Y-%m') as ym")
            ->selectRaw('COUNT(*) as submitted')
            ->selectRaw('SUM(CASE WHEN o.status = ? THEN 1 ELSE 0 END) as cancelled', [Order::STATUS_CANCELLED])
            ->get();

        $submitted = [];
        $cancelled = [];

        foreach ($rows as $row) {
            $submitted[$row->ym] = (int) $row->submitted;
            $cancelled[$row->ym] = (int) $row->cancelled;
        }

        return ['submitted' => $submitted, 'cancelled' => $cancelled];
    }

    /**
     * Distinct guests who did anything that costs money in a month — submitted an
     * order OR moved tokens. The two sources overlap (spending tokens on an order
     * writes both), so they are unioned per month rather than added.
     *
     * @return array<string, int>
     */
    private function activeBuyersByMonth(): array
    {
        $seen = []; // 'Y-m' => [user_id => true]

        $orderActivity = $this->guestOrders()
            ->whereNotNull('o.submitted_at')
            ->groupBy('ym', 'o.user_id')
            ->selectRaw("DATE_FORMAT(o.submitted_at, '%Y-%m') as ym, o.user_id as user_id")
            ->get();

        foreach ($orderActivity as $row) {
            $seen[$row->ym][$row->user_id] = true;
        }

        $ledgerActivity = $this->guestLedger()
            ->whereNotNull('t.created_at')
            ->groupBy('ym', 'a.user_id')
            ->selectRaw("DATE_FORMAT(t.created_at, '%Y-%m') as ym, a.user_id as user_id")
            ->get();

        foreach ($ledgerActivity as $row) {
            $seen[$row->ym][$row->user_id] = true;
        }

        return array_map('count', $seen);
    }

    /**
     * Open carts — `draft` orders holding at least one item.
     *
     * `draft` IS the cart: there is no cart table, and one draft order exists
     * per user at a time. Empty drafts are excluded because an empty cart is not
     * an abandoned one — it is a user who opened the marketplace.
     *
     * Age is measured from `updated_at`, i.e. time since the cart was last
     * TOUCHED, which is what "abandoned" means. `created_at` would keep ageing a
     * cart somebody edited this morning.
     *
     * @return array<string, mixed>
     */
    private function openCarts(): array
    {
        $carts = $this->guestOrders()
            ->leftJoin('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->where('o.status', Order::STATUS_DRAFT)
            ->groupBy('o.id', 'o.updated_at')
            ->havingRaw('COUNT(oi.id) >= 1')
            ->selectRaw('o.id as id, o.updated_at as updated_at, COUNT(oi.id) as items')
            ->get();

        $now = Carbon::now();
        $ages = [];
        $labels = [];
        $idle = 0;
        $items = 0;

        foreach ($carts as $cart) {
            $items += (int) $cart->items;

            // A draft with no updated_at cannot be aged. It is still an open
            // cart, so it stays in the count and is left out of the age stats
            // only — never silently dropped from both.
            if (! $cart->updated_at) {
                continue;
            }

            $days = max(0.0, Carbon::parse($cart->updated_at)->floatDiffInDays($now, false));
            $ages[] = $days;
            $labels[] = MarketplaceStats::ageBucket($days);

            if ($days > self::CART_IDLE_DAYS) {
                $idle++;
            }
        }

        return [
            'openCartCount' => $carts->count(),
            'openCartItems' => $items,
            'openCartMedianAge' => Statistics::median($ages),
            'openCartIdleCount' => $idle,
            'openCartIdleRate' => Statistics::rate($idle, count($ages)),
            'openCartAgeChart' => $this->toPie(
                MarketplaceStats::tally($labels, MarketplaceStats::AGE_BUCKETS)
            ),
        ];
    }

    /**
     * Favorites added per month. `user_favorite_domains` has no migration; the
     * shape comes from FavoriteController, which writes `created_at` on every
     * insert — so a monthly trend is computable.
     *
     * @return array<string, int>
     */
    private function favoritesByMonth(): array
    {
        return $this->guestFavorites()
            ->whereNotNull('f.created_at')
            ->groupBy('ym')
            ->selectRaw("DATE_FORMAT(f.created_at, '%Y-%m') as ym, COUNT(*) as c")
            ->pluck('c', 'ym')
            ->map(fn ($c) => (int) $c)
            ->all();
    }

    /**
     * Share of favorited domains that the same guest LATER ordered.
     *
     * "Later" is enforced (`order_items.created_at >= favorites.created_at`), so
     * a domain that was ordered first and favorited afterwards does not count as
     * the favorite driving the order.
     *
     * "Ordered" means an order that was SUBMITTED, not merely a cart line —
     * consistent with the rest of these pages, where submission is the counting
     * event. Two known undercounts, both in the honest direction: a favorite
     * removed after ordering leaves no row (the toggle DELETEs), and a favorite
     * added this week has had no time to convert.
     *
     * @return array<string, mixed>
     */
    private function favoriteConversion(): array
    {
        $total = (int) $this->guestFavorites()->count();

        $converted = (int) $this->guestFavorites()
            ->whereNotNull('f.created_at')
            ->whereExists(function (Builder $query) {
                $query->selectRaw('1')
                    ->from('order_items as oi')
                    ->join('orders as o2', 'o2.id', '=', 'oi.order_id')
                    ->whereNotNull('o2.submitted_at')
                    ->whereColumn('o2.user_id', 'f.user_id')
                    ->whereColumn('oi.website_id', 'f.website_id')
                    ->whereColumn('oi.created_at', '>=', 'f.created_at');
            })
            ->count();

        return [
            'favoriteTotal' => $total,
            'favoriteConverted' => $converted,
            'favoriteConversionRate' => Statistics::rate($converted, $total),
        ];
    }

    /* ────────────────────────── Revenue builders ─────────────────────────── */

    /**
     * PAID top-ups, dated by `paid_at` — the moment money actually arrived, not
     * when the attempt was created.
     *
     * Cash is grouped by currency and never folded together. Tokens are, because
     * the peg makes them one unit: 1 token = EUR 1, permanently.
     *
     * `tokens` is the peg-normalized revenue line; `bonus_tokens` is reported
     * separately (KPI 9) because it is the cost of the discount policy, not
     * revenue.
     *
     * @return array<string, mixed>
     */
    private function paidPurchasesByMonth(): array
    {
        $rows = $this->guestPurchases()
            ->where('p.status', TokenPurchase::STATUS_PAID)
            ->whereNotNull('p.paid_at')
            ->groupBy('ym', 'p.currency', 'p.package_key', 'p.user_id')
            ->selectRaw("DATE_FORMAT(p.paid_at, '%Y-%m') as ym")
            ->selectRaw('p.currency as currency, p.package_key as package_key, p.user_id as user_id')
            ->selectRaw('COUNT(*) as c, SUM(p.amount_minor) as minor, SUM(p.tokens) as tokens, SUM(p.bonus_tokens) as bonus')
            ->get();

        $revenueByCurrency = [];   // currency => 'Y-m' => minor
        $payersByCurrency = [];    // currency => 'Y-m' => [user_id => true]
        $tokensByMonth = [];
        $bonusByMonth = [];
        $packages = [];            // package_key => [count, tokens, bonus, minor per currency]

        foreach ($rows as $row) {
            $currency = (string) $row->currency;
            $minor = (int) $row->minor;

            $revenueByCurrency[$currency][$row->ym] = ($revenueByCurrency[$currency][$row->ym] ?? 0) + $minor;
            $payersByCurrency[$currency][$row->ym][$row->user_id] = true;

            $tokensByMonth[$row->ym] = ($tokensByMonth[$row->ym] ?? 0) + (int) $row->tokens;
            $bonusByMonth[$row->ym] = ($bonusByMonth[$row->ym] ?? 0) + (int) $row->bonus;

            $key = (string) $row->package_key;
            $packages[$key] ??= ['count' => 0, 'tokens' => 0, 'bonus' => 0, 'revenue' => []];
            $packages[$key]['count'] += (int) $row->c;
            $packages[$key]['tokens'] += (int) $row->tokens;
            $packages[$key]['bonus'] += (int) $row->bonus;
            $packages[$key]['revenue'][$currency] = ($packages[$key]['revenue'][$currency] ?? 0) + $minor;
        }

        $currencies = array_keys($revenueByCurrency);
        sort($currencies);

        // Package mix ranked by tokens sold — the peg-normalized size of each
        // package, which is comparable across currencies where cash is not.
        $totalTokens = array_sum(array_column($packages, 'tokens'));
        $packageRows = [];

        foreach ($packages as $key => $package) {
            $packageRows[] = [
                'key' => $key,
                'label' => ucfirst($key),
                'count' => $package['count'],
                'tokens' => $package['tokens'],
                'bonus' => $package['bonus'],
                'share' => Statistics::rate($package['tokens'], $totalTokens),
                'revenue' => array_map(
                    fn ($minor) => MarketplaceStats::fromMinor((int) $minor),
                    $package['revenue']
                ),
            ];
        }

        usort($packageRows, fn (array $a, array $b) => [$b['tokens'], $b['count']] <=> [$a['tokens'], $a['count']]);

        return [
            'byMonth' => $tokensByMonth,
            'currencies' => $currencies,
            'revenueByCurrency' => $revenueByCurrency,
            'payersByCurrency' => $payersByCurrency,
            'tokensByMonth' => $tokensByMonth,
            'bonusByMonth' => $bonusByMonth,
            'packageRows' => $packageRows,
        ];
    }

    /**
     * Top-up ATTEMPTS by status, dated by `created_at`.
     *
     * `paid_at` cannot date this one: a failed or expired attempt never gets a
     * `paid_at`, so dating by it would silently drop every failure — the exact
     * rows a success rate exists to measure. The range therefore selects an
     * ATTEMPT cohort.
     *
     * The rate follows the spec: paid ÷ (paid + failed + expired). `pending`
     * (undecided) and `refunded` (a payment that SUCCEEDED and was reversed
     * later — KPI 8's subject) sit outside both sides of it and are reported in
     * the table instead of distorting the rate.
     *
     * @return array<string, mixed>
     */
    private function purchaseAttemptsByMonth(): array
    {
        $rows = $this->guestPurchases()
            ->groupBy('ym', 'p.status')
            ->selectRaw("DATE_FORMAT(p.created_at, '%Y-%m') as ym, p.status as status, COUNT(*) as c")
            ->get();

        $byMonth = [];   // 'Y-m' => [status => count]
        $totals = [];    // status => count

        foreach ($rows as $row) {
            $byMonth[$row->ym][$row->status] = ((int) ($byMonth[$row->ym][$row->status] ?? 0)) + (int) $row->c;
            $totals[$row->status] = ($totals[$row->status] ?? 0) + (int) $row->c;
        }

        $successRateByMonth = [];
        foreach ($byMonth as $ym => $statuses) {
            $decided = ($statuses[TokenPurchase::STATUS_PAID] ?? 0)
                + ($statuses[TokenPurchase::STATUS_FAILED] ?? 0)
                + ($statuses['expired'] ?? 0);

            $successRateByMonth[$ym] = Statistics::rate($statuses[TokenPurchase::STATUS_PAID] ?? 0, $decided);
        }

        $attempted = array_sum($totals);
        $statusRows = [];
        // Config order, then anything unexpected the column happens to hold, so
        // a status added later still appears instead of vanishing from the total.
        $statuses = array_values(array_unique(array_merge(
            [TokenPurchase::STATUS_PAID, TokenPurchase::STATUS_FAILED, 'expired',
                TokenPurchase::STATUS_PENDING, TokenPurchase::STATUS_REFUNDED],
            array_keys($totals)
        )));

        foreach ($statuses as $status) {
            if (($totals[$status] ?? 0) === 0) {
                continue;
            }
            $statusRows[] = [
                'label' => ucfirst($status),
                'count' => $totals[$status],
                'share' => Statistics::rate($totals[$status], $attempted),
            ];
        }

        return [
            'byMonth' => $successRateByMonth,
            'totals' => $totals,
            'rows' => $statusRows,
            'successRateByMonth' => $successRateByMonth,
        ];
    }

    /**
     * The token ledger per month, by type.
     *
     * Credits are `purchase` + `bonus` (tokens arriving), debits are `spend`
     * (tokens leaving, stored negative and reported positive). `refund` and
     * `adjustment` are pulled out separately for KPI 8.
     *
     * The ledger — not `token_purchases.updated_at` — is what dates a refund:
     * a refund writes its own immutable row with its own `created_at`, whereas
     * `updated_at` on the purchase moves on any later write.
     *
     * @return array<string, array<string, int>>
     */
    private function ledgerByMonth(): array
    {
        $rows = $this->guestLedger()
            ->whereNotNull('t.created_at')
            ->groupBy('ym', 't.type')
            ->selectRaw("DATE_FORMAT(t.created_at, '%Y-%m') as ym, t.type as type, SUM(t.amount) as amount")
            ->get();

        $credits = [];
        $debits = [];
        $refunds = [];
        $adjustments = [];
        $net = [];

        foreach ($rows as $row) {
            $amount = (int) $row->amount;
            $net[$row->ym] = ($net[$row->ym] ?? 0) + $amount;

            match ($row->type) {
                TokenTransaction::TYPE_PURCHASE, TokenTransaction::TYPE_BONUS => $credits[$row->ym] = ($credits[$row->ym] ?? 0) + $amount,
                TokenTransaction::TYPE_SPEND => $debits[$row->ym] = ($debits[$row->ym] ?? 0) + abs($amount),
                TokenTransaction::TYPE_REFUND => $refunds[$row->ym] = ($refunds[$row->ym] ?? 0) + abs($amount),
                TokenTransaction::TYPE_ADJUSTMENT => $adjustments[$row->ym] = ($adjustments[$row->ym] ?? 0) + $amount,
                // `expiry` moves the balance (so it is in $net) but belongs to
                // neither the purchased-vs-spent question nor the refund one.
                default => null,
            };
        }

        return [
            'byMonth' => $net,
            'net' => $net,
            'credits' => $credits,
            'debits' => $debits,
            'refunds' => $refunds,
            'adjustments' => $adjustments,
        ];
    }

    /**
     * Prepaid credit owed to guests, both ways round.
     *
     * `balance_cached` is a CACHE; `token_transactions` is the truth. Reporting
     * only the cache would hide exactly the drift the scheduled reconciliation
     * exists to catch, so both are shown and the difference is surfaced.
     *
     * @return array{cached: int, derived: int, drift: int}
     */
    private function tokenLiability(): array
    {
        $cached = (int) DB::table('token_accounts as a')
            ->join('users as u', fn ($join) => $join->on('u.id', '=', 'a.user_id')->where('u.role', '=', 'guest'))
            ->sum('a.balance_cached');

        $derived = (int) $this->guestLedger()->sum('t.amount');

        return ['cached' => $cached, 'derived' => $derived, 'drift' => $cached - $derived];
    }

    /**
     * Order economics: average value per SUBMITTED order, and the price split by
     * article type.
     *
     * `orders` has no total column, so an order's value is SUM(unit_price) over
     * its items — and `order_items` has no quantity column (one row per website,
     * unique per order), so quantity is implicitly 1.
     *
     * The article-type split is PER ITEM, not per order, and deliberately: one
     * order may hold both standard and sensitive placements, so there is no such
     * thing as a "sensitive order" to average.
     *
     * Amounts are euro — prices snap from the website's price columns, and the
     * token peg makes a token and a euro the same unit.
     *
     * @return array<string, mixed>
     */
    private function orderValuesByMonth(): array
    {
        $orders = $this->guestOrders()
            ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->whereNotNull('o.submitted_at')
            ->groupBy('o.id', 'ym')
            ->selectRaw("o.id as id, DATE_FORMAT(o.submitted_at, '%Y-%m') as ym, SUM(oi.unit_price) as total")
            ->get();

        $valuesByMonth = [];
        $all = [];

        foreach ($orders as $order) {
            $total = (float) $order->total;
            $valuesByMonth[$order->ym][] = $total;
            $all[] = $total;
        }

        $aovByMonth = array_map(
            fn (array $values) => round(array_sum($values) / count($values), 2),
            $valuesByMonth
        );

        $typeRows = $this->guestOrders()
            ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->whereNotNull('o.submitted_at')
            ->groupBy('oi.article_type')
            ->selectRaw('oi.article_type as type, COUNT(*) as items, SUM(oi.unit_price) as total')
            ->get();

        $totalItems = (int) $typeRows->sum('items');
        $articleTypeRows = $typeRows
            ->map(fn ($row) => [
                'label' => ucfirst((string) $row->type),
                'items' => (int) $row->items,
                'total' => round((float) $row->total, 2),
                'average' => $row->items > 0 ? round((float) $row->total / (int) $row->items, 2) : null,
                'share' => Statistics::rate((int) $row->items, $totalItems),
            ])
            ->sortByDesc('items')
            ->values()
            ->all();

        return [
            'byMonth' => $aovByMonth,
            'aovByMonth' => $aovByMonth,
            'orderCount' => count($all),
            'average' => $all ? round(array_sum($all) / count($all), 2) : null,
            'articleTypeRows' => $articleTypeRows,
        ];
    }

    /**
     * Where guest orders currently stand, plus how long a completed one took.
     *
     * `status_changed_at` holds only the LAST transition, so no time-in-stage
     * history exists. It IS a valid completion timestamp for an order that is
     * currently `completed` — the last transition is the one that completed it —
     * which is why the duration is measured on completed orders only.
     *
     * @return array<string, mixed>
     */
    private function orderPipeline(): array
    {
        $counts = $this->guestOrders()
            ->groupBy('o.status')
            ->selectRaw('o.status as status, COUNT(*) as c')
            ->pluck('c', 'status');

        // Enum order first, then any status the column holds that the enum does
        // not, so the chart total always equals the order count beside it.
        $statuses = array_values(array_unique(array_merge(
            self::ORDER_STATUS_ORDER,
            $counts->keys()->all()
        )));

        $pipeline = [];
        foreach ($statuses as $status) {
            $count = (int) ($counts[$status] ?? 0);
            if ($count === 0) {
                continue;
            }
            $pipeline[Order::STATUS_LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status))] = $count;
        }

        $completed = $this->guestOrders()
            ->where('o.status', Order::STATUS_COMPLETED)
            ->whereNotNull('o.submitted_at')
            ->whereNotNull('o.status_changed_at')
            ->select('o.submitted_at', 'o.status_changed_at')
            ->get();

        $durations = [];
        foreach ($completed as $order) {
            $days = Carbon::parse($order->submitted_at)
                ->floatDiffInDays(Carbon::parse($order->status_changed_at), false);

            // A completion stamped before its submission is corrupt, not fast —
            // it would drag the median down and never show up as an error.
            if ($days >= 0) {
                $durations[] = $days;
            }
        }

        return [
            'pipelineChart' => $this->toPie($pipeline),
            'pipelineTotal' => array_sum($pipeline),
            'medianFulfilmentDays' => Statistics::median($durations),
            'measuredCompletions' => count($durations),
        ];
    }

    /* ───────────────────────── Shared scopes / shaping ───────────────────── */

    /** Orders joined to their owner, filtered to guests. Alias: `o`, `u`. */
    private function guestOrders(): Builder
    {
        return DB::table('orders as o')
            ->join('users as u', fn ($join) => $join->on('u.id', '=', 'o.user_id')->where('u.role', '=', 'guest'));
    }

    /** Top-ups joined to their owner, filtered to guests. Alias: `p`, `u`. */
    private function guestPurchases(): Builder
    {
        return DB::table('token_purchases as p')
            ->join('users as u', fn ($join) => $join->on('u.id', '=', 'p.user_id')->where('u.role', '=', 'guest'));
    }

    /** Ledger rows joined through their account to the owner. Alias: `t`, `a`, `u`. */
    private function guestLedger(): Builder
    {
        return DB::table('token_transactions as t')
            ->join('token_accounts as a', 'a.id', '=', 't.token_account_id')
            ->join('users as u', fn ($join) => $join->on('u.id', '=', 'a.user_id')->where('u.role', '=', 'guest'));
    }

    /** Favorites joined to their owner, filtered to guests. Alias: `f`, `u`. */
    private function guestFavorites(): Builder
    {
        return DB::table('user_favorite_domains as f')
            ->join('users as u', fn ($join) => $join->on('u.id', '=', 'f.user_id')->where('u.role', '=', 'guest'));
    }

    /**
     * A label => count map as the {labels, series} pair the pie-table partial and
     * ApexCharts both take.
     *
     * @param  array<string, int>  $counts
     * @return array{labels: array<int, string>, series: array<int, int>}
     */
    private function toPie(array $counts): array
    {
        return [
            'labels' => array_keys($counts),
            'series' => array_map('intval', array_values($counts)),
        ];
    }
}
