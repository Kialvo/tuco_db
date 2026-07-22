<?php

namespace App\Http\Controllers;

use App\Support\Statistics;
use App\Support\StatsDateRange;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    /**
     * Database Statistics — website status split (donut) + active domains.
     * Read-only. Further metrics/charts wired in a later pass.
     */
    public function publishers()
    {
        // Model query auto-excludes soft-deleted (SoftDeletes); status is
        // lowercase-normalized on write, so the keys are stable slugs.
        $counts = \App\Models\Website::selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $statusChart = [
            'labels' => ['Active', 'Inactive', 'Blacklist'],
            'series' => [
                (int) ($counts['active'] ?? 0),
                (int) ($counts['inactive'] ?? 0),
                (int) ($counts['blacklist'] ?? 0),
            ],
        ];

        $activeDomains = (int) ($counts['active'] ?? 0);

        // Active websites by country — top 10 + "Other" (81 distinct countries
        // is too many slices for a readable pie). Country_id is nullable, so
        // null-country actives are folded into "Other" to keep totals honest.
        $countryRows = \App\Models\Website::query()
            ->where('websites.status', 'active')
            ->join('countries', 'websites.country_id', '=', 'countries.id')
            ->selectRaw('countries.country_name as name, COUNT(*) as c')
            ->groupBy('countries.country_name')
            ->orderByDesc('c')
            ->pluck('c', 'name');

        $topCountries = $countryRows->take(10);
        $otherTotal = (int) $countryRows->slice(10)->sum()
            + \App\Models\Website::where('status', 'active')->whereNull('country_id')->count();

        $countryLabels = $topCountries->keys()->all();
        $countrySeries = $topCountries->map(fn ($v) => (int) $v)->values()->all();

        if ($otherTotal > 0) {
            $countryLabels[] = 'Other';
            $countrySeries[] = $otherTotal;
        }

        $countryChart = ['labels' => $countryLabels, 'series' => $countrySeries];

        // Active websites by type — 5 clean buckets, empty/null folded to "(None)".
        $typeRows = \App\Models\Website::query()
            ->where('status', 'active')
            ->selectRaw('COALESCE(NULLIF(type_of_website, ""), "(None)") as name, COUNT(*) as c')
            ->groupBy('name')
            ->orderByDesc('c')
            ->pluck('c', 'name');

        $typeChart = [
            'labels' => $typeRows->keys()->all(),
            'series' => $typeRows->map(fn ($v) => (int) $v)->values()->all(),
        ];

        // Active websites by language — top 10 + "Other" (23 distinct), null
        // languages folded into "Other" to keep the total honest.
        $langRows = \App\Models\Website::query()
            ->where('websites.status', 'active')
            ->join('languages', 'websites.language_id', '=', 'languages.id')
            ->selectRaw('languages.name as name, COUNT(*) as c')
            ->groupBy('languages.name')
            ->orderByDesc('c')
            ->pluck('c', 'name');

        $topLang = $langRows->take(10);
        $otherLang = (int) $langRows->slice(10)->sum()
            + \App\Models\Website::where('status', 'active')->whereNull('language_id')->count();

        $langLabels = $topLang->keys()->all();
        $langSeries = $topLang->map(fn ($v) => (int) $v)->values()->all();

        if ($otherLang > 0) {
            $langLabels[] = 'Other';
            $langSeries[] = $otherLang;
        }

        $languageChart = ['labels' => $langLabels, 'series' => $langSeries];

        return view('stats.publishers', compact(
            'statusChart', 'activeDomains', 'countryChart', 'typeChart', 'languageChart'
        ));
    }

    /**
     * Campaigns Statistics — completed campaigns per month, stacked by the
     * completion status (the config "Completed" group). Read-only.
     */
    public function campaigns(Request $request)
    {
        // Shared date-range picker (?date_from / ?date_to). NOTE the two widget
        // families on this page are dated by DIFFERENT columns, because no single
        // date fits both — each is labelled accordingly in the view:
        //   • completed campaigns → completion date (latest publication_date)
        //   • approval funnel     → proposal date (storage.created_at)
        // storage.updated_at is NOT usable as a decision date: a backfill
        // collapsed all 260 campaign-linked rows into a single month.
        [$dateFrom, $dateTo] = StatsDateRange::fromRequest($request);

        // Ordered list of "Completed" statuses (single source of truth: config).
        $completedStatuses = config('linkbuilding.campaign_statuses.Completed', []);

        // Fixed colour per status, echoing the screenshot's deadline-respect
        // coding (in-time green, our-fault red, publisher gold, etc.).
        $statusColors = [
            'Completed in time' => '#10b981', // emerald
            'Completed with delay (Our fault)' => '#ef4444', // red
            'Completed with Delay – Late Budget Approval' => '#f59e0b', // amber
            'Completed with Delay – Sites Added Mid-Campaign' => '#ec4899', // pink
            'Completed with Delay – Client Unresponsive' => '#64748b', // slate
            "Completed with Delay – Publisher's Fault" => '#eab308', // gold
        ];

        // Completed campaigns bucketed by completion month + status. The
        // completion date is derived (Campaign::liveCompletionDate): the most
        // recent publication LIVE DATE. Campaigns with no live publication
        // can't be placed on the month axis, so they're skipped. SoftDeletes
        // auto-excludes trashed campaigns.
        $campaigns = \App\Models\Campaign::query()
            ->whereIn('status', $completedStatuses)
            ->withMax('publications as latest_live_date', 'publication_date')
            ->get();

        // [ym][status] => count. Campaigns whose completion date falls outside the
        // selected range are dropped here, so the chart matches the picker.
        $lookup = [];
        foreach ($campaigns as $c) {
            if (! $c->latest_live_date) {
                continue;
            }
            $completedOn = \Carbon\Carbon::parse($c->latest_live_date)->startOfDay();
            if ($dateFrom && $completedOn->lt($dateFrom)) {
                continue;
            }
            if ($dateTo && $completedOn->gt($dateTo)) {
                continue;
            }
            $ym = substr((string) $c->latest_live_date, 0, 7); // 'Y-m'
            $lookup[$ym][$c->status] = ($lookup[$ym][$c->status] ?? 0) + 1;
        }

        // Month axis. With a picked range the axis IS the range (so an empty
        // range still renders its months rather than silently falling back to
        // "last 12 months" and looking unfiltered); otherwise trailing 12
        // months, extended back if older data exists.
        $end = $dateTo ? $dateTo->copy()->startOfMonth() : \Carbon\Carbon::now()->startOfMonth();
        $start = $dateFrom ? $dateFrom->copy()->startOfMonth() : $end->copy()->subMonths(11);

        if (! $dateFrom) {
            $earliest = $lookup ? min(array_keys($lookup)) : null;
            if ($earliest) {
                $earliestC = \Carbon\Carbon::createFromFormat('Y-m', $earliest)->startOfMonth();
                if ($earliestC->lt($start)) {
                    $start = $earliestC;
                }
            }
        }

        if ($start->gt($end)) {
            $start = $end->copy();
        }

        $months = []; // ['Y-m' => 'Mon YYYY']
        for ($m = $start->copy(); $m->lte($end); $m->addMonth()) {
            $months[$m->format('Y-m')] = $m->format('M Y');
        }

        // One series per status that actually occurs in-window (config order).
        $series = [];
        $colors = [];
        foreach ($completedStatuses as $status) {
            $data = [];
            $total = 0;
            foreach (array_keys($months) as $ym) {
                $v = $lookup[$ym][$status] ?? 0;
                $data[] = $v;
                $total += $v;
            }
            if ($total > 0) {
                $series[] = ['name' => $status, 'data' => $data];
                $colors[] = $statusColors[$status] ?? '#94a3b8';
            }
        }

        $campaignsChart = [
            'categories' => array_values($months),
            'series' => $series,
            'colors' => $colors,
            'totalCompleted' => array_sum(array_map(fn ($s) => array_sum($s['data']), $series)),
        ];

        return view('stats.campaigns', compact('campaignsChart') + $this->publicationDecisions($dateFrom, $dateTo) + [
            'dateFrom' => $dateFrom?->toDateString(),
            'dateTo' => $dateTo?->toDateString(),
        ]);
    }

    /**
     * Site-approval funnel over CRM campaign-linked publications: approval rate,
     * rejection rate and rejection reasons — overall and per client.
     *
     * Scope is deliberately `storage.lb_campaign_id NOT NULL`. Legacy Storage
     * rows are excluded because, before the CRM, a row was usually only created
     * AFTER the client had accepted the site — including them would report a
     * survivorship-biased ~91% approval instead of the real one.
     *
     * The rates use a DECIDED-ONLY denominator (approved + rejected), so they
     * sum to 100%; pending proposals are reported separately.
     *
     * DATE AXIS — `storage.created_at`, i.e. WHEN THE SITE WAS PROPOSED, not when
     * it was decided. There is no decision-date column, and `updated_at` cannot
     * stand in for one: a backfill collapsed every campaign-linked row into a
     * single month, so it carries no signal. The range therefore selects a
     * PROPOSAL COHORT — "of the sites we pitched in this period, how many were
     * approved" — which also means a cohort's recent months carry more pending
     * rows, since some decisions have not landed yet.
     */
    private function publicationDecisions(?Carbon $dateFrom = null, ?Carbon $dateTo = null): array
    {
        // Company × status counts. Storage's SoftDeletes global scope excludes
        // trashed publications; the raw join does NOT carry Campaign's scope, so
        // trashed campaigns are excluded explicitly.
        $rows = \App\Models\Storage::query()
            ->join('lb_campaigns', 'lb_campaigns.id', '=', 'storage.lb_campaign_id')
            ->whereNull('lb_campaigns.deleted_at')
            ->when($dateFrom, fn ($q) => $q->whereDate('storage.created_at', '>=', $dateFrom->toDateString()))
            ->when($dateTo, fn ($q) => $q->whereDate('storage.created_at', '<=', $dateTo->toDateString()))
            ->leftJoin('companies', 'companies.id', '=', 'lb_campaigns.company_id')
            ->selectRaw("COALESCE(NULLIF(companies.name, ''), 'Unassigned') as company")
            ->selectRaw('storage.status as status, COUNT(*) as c')
            ->groupBy('company', 'storage.status')
            ->get();

        $blank = ['approved' => 0, 'rejected' => 0, 'pending' => 0];
        $totals = $blank;
        $byClient = [];          // company => [approved, rejected, pending]
        $reasonCounts = [];      // rejected slug => count
        $reasonByClient = [];    // company => [slug => count]

        foreach ($rows as $row) {
            $decision = \App\Support\PublicationStatus::decision($row->status);
            $count = (int) $row->c;
            $company = (string) $row->company;

            $totals[$decision] += $count;
            $byClient[$company] ??= $blank;
            $byClient[$company][$decision] += $count;

            if ($decision === 'rejected') {
                $reasonCounts[$row->status] = ($reasonCounts[$row->status] ?? 0) + $count;
                $reasonByClient[$company][$row->status] = ($reasonByClient[$company][$row->status] ?? 0) + $count;
            }
        }

        $decided = $totals['approved'] + $totals['rejected'];

        $decisionTotals = [
            'approved' => $totals['approved'],
            'rejected' => $totals['rejected'],
            'pending' => $totals['pending'],
            'decided' => $decided,
            'proposed' => $decided + $totals['pending'],
            'approvalRate' => Statistics::rate($totals['approved'], $decided),
            'rejectionRate' => Statistics::rate($totals['rejected'], $decided),
        ];

        // Per client. A company can have proposals but NO decision yet (pitched,
        // client silent) — its rates stay null so the view renders "—", and it is
        // kept out of the 100%-stacked chart, whose category total would be zero.
        $decisionByClient = [];
        foreach ($byClient as $company => $c) {
            $clientDecided = $c['approved'] + $c['rejected'];
            $decisionByClient[] = [
                'name' => $company,
                'approved' => $c['approved'],
                'rejected' => $c['rejected'],
                'pending' => $c['pending'],
                'decided' => $clientDecided,
                'proposed' => $clientDecided + $c['pending'],
                'approvalRate' => Statistics::rate($c['approved'], $clientDecided),
                'rejectionRate' => Statistics::rate($c['rejected'], $clientDecided),
            ];
        }

        // Ranked by decided volume, then by proposals; "Unassigned" is a
        // data-quality bucket, never ranked (mirrors buildRevenuesPerCompany).
        usort($decisionByClient, function (array $a, array $b) {
            if (($a['name'] === 'Unassigned') !== ($b['name'] === 'Unassigned')) {
                return $a['name'] === 'Unassigned' ? 1 : -1;
            }

            return [$b['decided'], $b['proposed']] <=> [$a['decided'], $a['proposed']];
        });

        // Rejection reasons, in config order, only those that actually occur.
        $reasonSlugs = array_filter(
            \App\Support\PublicationStatus::slugsByDecision('rejected'),
            fn ($slug) => ($reasonCounts[$slug] ?? 0) > 0
        );

        $rejectionReasons = [];
        foreach ($reasonSlugs as $slug) {
            $rejectionReasons[] = [
                'slug' => $slug,
                'label' => \App\Support\PublicationStatus::label($slug) ?? $slug,
                'count' => $reasonCounts[$slug],
                'share' => Statistics::rate($reasonCounts[$slug], $totals['rejected']),
            ];
        }

        // Reason × client matrix — one stacked series per reason, clients with at
        // least one rejection, most-rejected first.
        $rejectedClients = array_values(array_filter(
            array_column($decisionByClient, 'name'),
            fn ($name) => ! empty($reasonByClient[$name])
        ));

        $reasonSeries = [];
        foreach ($reasonSlugs as $slug) {
            $reasonSeries[] = [
                'name' => \App\Support\PublicationStatus::label($slug) ?? $slug,
                'data' => array_map(
                    fn ($name) => (int) ($reasonByClient[$name][$slug] ?? 0),
                    $rejectedClients
                ),
            ];
        }

        return [
            'decisionTotals' => $decisionTotals,
            'decisionByClient' => $decisionByClient,
            'rejectionReasons' => $rejectionReasons,
            'rejectionReasonChart' => [
                'labels' => array_column($rejectionReasons, 'label'),
                'series' => array_column($rejectionReasons, 'count'),
            ],
            'rejectionReasonsByClient' => [
                'clients' => $rejectedClients,
                'series' => $reasonSeries,
            ],
        ];
    }
}
