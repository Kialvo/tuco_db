<?php

namespace App\Http\Controllers;

use App\Models\Storage;
use App\Support\Statistics;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class StorageStatsController extends Controller
{
    private const WINDOW_OPTIONS = ['all', '12', '24', '36', '60'];

    private const GRANULARITY_OPTIONS = ['monthly', 'quarterly'];

    /** How many individual companies get their own stacked series (rest → "Others"). */
    private const REVENUE_TOP_COMPANIES = 8;

    /** How many individual websites get their own stacked series (rest → "Others"). */
    private const PUBLISHER_TOP_SITES = 8;

    public function index(Request $request)
    {
        // Same date range the page-level widgets use, so the publisher widgets
        // stay in sync with the date picker.
        [$dateFrom, $dateTo] = $this->normalizeSelectedDates(
            $this->parseSelectedDate($request->query('date_from')),
            $this->parseSelectedDate($request->query('date_to'))
        );

        $publisher = $this->publisherTimeSeries(
            $this->cleanSiteParam($request->query('article_sites')),
            $this->cleanSiteParam($request->query('spend_sites')),
            $dateFrom,
            $dateTo
        );

        return view('storages.stats', $this->computeStats($request) + $publisher);
    }

    /**
     * Financial Statistics — Total Net Profit + Net Profit over time.
     * Shares the exact filter/aggregation pipeline as Publication Stats;
     * the view renders only the profit card + chart. Read-only.
     */
    public function financial(Request $request)
    {
        return view('stats.financial', $this->computeStats($request));
    }

    /**
     * Build the shared stats payload (filters + aggregated series) consumed by
     * both the Publication and Financial Stats pages.
     */
    private function computeStats(Request $request): array
    {
        $window = $request->query('window', 'all');
        if (! in_array($window, self::WINDOW_OPTIONS, true)) {
            $window = 'all';
        }

        $granularity = $request->query('granularity', 'monthly');
        if (! in_array($granularity, self::GRANULARITY_OPTIONS, true)) {
            $granularity = 'monthly';
        }

        [$dateFrom, $dateTo] = $this->normalizeSelectedDates(
            $this->parseSelectedDate($request->query('date_from')),
            $this->parseSelectedDate($request->query('date_to'))
        );
        $hasCustomRange = $dateFrom !== null || $dateTo !== null;

        $monthExpr = DB::raw("DATE_FORMAT(publication_date, '%Y-%m')");

        $baseQuery = Storage::query()
            ->where('status', 'article_published')
            ->whereNotNull('publication_date');

        $bounds = (clone $baseQuery)
            ->selectRaw('MIN(publication_date) as min_publication_date')
            ->selectRaw('MAX(publication_date) as max_publication_date')
            ->first();

        $rowsQuery = clone $baseQuery;
        if ($dateFrom) {
            $rowsQuery->whereDate('publication_date', '>=', $dateFrom->toDateString());
        }
        if ($dateTo) {
            $rowsQuery->whereDate('publication_date', '<=', $dateTo->toDateString());
        }

        $rows = $rowsQuery
            ->selectRaw("DATE_FORMAT(publication_date, '%Y-%m') as month_key")
            ->selectRaw('COUNT(*) as published_count')
            ->selectRaw('COALESCE(SUM(profit), 0) as net_profit')
            ->groupBy($monthExpr)
            ->orderBy($monthExpr)
            ->get();

        // Raw per-row period values (days) for the SAME filtered set, keyed by
        // publication month, so we can compute a MEDIAN per bucket in PHP —
        // MySQL/MariaDB has no MEDIAN() aggregate. Nulls/blanks are skipped
        // per-metric (a row may have one period but not the other).
        $rawPeriodRows = (clone $baseQuery)
            ->when($dateFrom, fn ($q) => $q->whereDate('publication_date', '>=', $dateFrom->toDateString()))
            ->when($dateTo, fn ($q) => $q->whereDate('publication_date', '<=', $dateTo->toDateString()))
            ->selectRaw("DATE_FORMAT(publication_date, '%Y-%m') as month_key, copywriter_period, publisher_period")
            ->get();

        $copyPeriodByMonth = [];
        $publisherPeriodByMonth = [];
        foreach ($rawPeriodRows as $row) {
            if (is_numeric($row->copywriter_period)) {
                $copyPeriodByMonth[$row->month_key][] = (float) $row->copywriter_period;
            }
            if (is_numeric($row->publisher_period)) {
                $publisherPeriodByMonth[$row->month_key][] = (float) $row->publisher_period;
            }
        }

        [$seriesStart, $seriesEnd] = $this->resolveSeriesBounds(
            $dateFrom,
            $dateTo,
            $bounds?->min_publication_date,
            $bounds?->max_publication_date
        );

        $monthlyPoints = $this->buildMonthlySeries(
            $rows,
            $hasCustomRange ? $seriesStart : null,
            $hasCustomRange ? $seriesEnd : null
        );
        $windowedPoints = $hasCustomRange
            ? $monthlyPoints
            : $this->applyWindow($monthlyPoints, $window);
        $seriesPoints = $granularity === 'quarterly'
            ? $this->toQuarterlySeries($windowedPoints)
            : $windowedPoints;

        $labels = array_column($seriesPoints, 'label');
        $publishedSeries = array_map(static fn (array $point) => (int) $point['published'], $seriesPoints);
        $profitSeries = array_map(static fn (array $point) => (float) $point['profit'], $seriesPoints);

        // Median-days series, aligned to the same labels/order as above.
        $copyMedianSeries = Statistics::medianSeries($windowedPoints, $copyPeriodByMonth, $granularity);
        $publisherMedianSeries = Statistics::medianSeries($windowedPoints, $publisherPeriodByMonth, $granularity);

        // Raw MONTHLY published series for the "Guest Posts Published" widget.
        // Kept independent of the page-level $granularity so that widget's own
        // Monthly / Quarterly / Yearly toggle can re-aggregate it client-side.
        $guestPostsMonthly = array_map(static fn (array $point) => [
            'label' => $point['label'],          // "Mon YYYY"
            'value' => (int) $point['published'],
        ], $windowedPoints);

        // Same idea for the "Net Profit" widget: monthly source, summed
        // client-side by its own Monthly / Quarterly / Yearly toggle (profit
        // is additive, so quarter/year buckets are a straight sum).
        $netProfitMonthly = array_map(static fn (array $point) => [
            'label' => $point['label'],
            'value' => (float) $point['profit'],
        ], $windowedPoints);

        // Revenues per client — a company × month matrix (top companies as their
        // own stacked series, the rest bucketed into "Others", unresolved rows kept
        // separate). Dates come from the LIVE DATE (publication_date) column, same
        // filtered set as above. The widget's own toggle re-buckets it client-side.
        $revenuePerCompany = $this->buildRevenuesPerCompany($baseQuery, $dateFrom, $dateTo, $windowedPoints);

        return [
            'labels' => $labels,
            'publishedSeries' => $publishedSeries,
            'guestPostsMonthly' => $guestPostsMonthly,
            'netProfitMonthly' => $netProfitMonthly,
            'revenuePerCompanyMonths' => $revenuePerCompany['months'],
            'revenuePerCompanySeries' => $revenuePerCompany['series'],
            'revenuePerCompanyList' => $revenuePerCompany['companies'],
            'profitSeries' => $profitSeries,
            'totalPublished' => array_sum($publishedSeries),
            'totalNetProfit' => array_sum($profitSeries),
            'copyMedianSeries' => $copyMedianSeries,
            'publisherMedianSeries' => $publisherMedianSeries,
            'window' => $window,
            'granularity' => $granularity,
            'dateFrom' => $dateFrom?->toDateString(),
            'dateTo' => $dateTo?->toDateString(),
            'hasCustomRange' => $hasCustomRange,
            'windowOptions' => [
                '12' => 'Last 12 months',
                '24' => 'Last 24 months',
                '36' => 'Last 36 months',
                '60' => 'Last 60 months',
                'all' => 'All time',
            ],
            'granularityOptions' => [
                'monthly' => 'Monthly',
                'quarterly' => 'Quarterly',
            ],
            'rangeLabel' => $this->buildRangeLabel($seriesPoints, $dateFrom, $dateTo),
            'pointsCount' => count($seriesPoints),
        ];
    }

    /**
     * Build the "Revenues per Client" stacked-bar payload: a company × month matrix
     * aligned to the already-windowed month axis. Revenue is `total_revenues`; the
     * date is the LIVE DATE (`publication_date`); the stack is grouped by the client
     * company (storage → client → company). The top N companies (by revenue within
     * the visible window) each get a series; the remaining companies collapse into
     * "Others" and rows with no resolvable company into "Unassigned" (kept last).
     * Leading/trailing months with no revenue are trimmed so the chart starts at the
     * first month that actually has data.
     *
     * @param  array  $windowedPoints  The month sequence [{month:'Y-m', label:'M Y', ...}].
     * @return array{months: string[], series: array<int, array{name:string, data:float[]}>}
     */
    private function buildRevenuesPerCompany($baseQuery, ?Carbon $dateFrom, ?Carbon $dateTo, array $windowedPoints): array
    {
        $monthKeys = array_column($windowedPoints, 'month');
        $monthLabels = array_column($windowedPoints, 'label');

        if (empty($monthKeys)) {
            return ['months' => [], 'series' => []];
        }

        $monthKeySet = array_flip($monthKeys);

        $rows = (clone $baseQuery)
            ->when($dateFrom, fn ($q) => $q->whereDate('publication_date', '>=', $dateFrom->toDateString()))
            ->when($dateTo, fn ($q) => $q->whereDate('publication_date', '<=', $dateTo->toDateString()))
            ->selectRaw("DATE_FORMAT(publication_date, '%Y-%m') as month_key")
            ->selectRaw('client_id, COALESCE(total_revenues, 0) as revenue')
            ->get();

        // Resolve each client to its company name once (client → company).
        // DB::table so soft-deleted clients/companies still resolve their label.
        $companyByClient = DB::table('clients')
            ->join('companies', 'companies.id', '=', 'clients.company_id')
            ->whereIn('clients.id', $rows->pluck('client_id')->filter()->unique()->values())
            ->pluck('companies.name', 'clients.id');

        // [companyName][monthKey] => summed revenue, restricted to visible months.
        $matrix = [];
        $bucketTotals = [];
        foreach ($rows as $row) {
            if (! isset($monthKeySet[$row->month_key])) {
                continue;
            }

            $name = ($row->client_id && isset($companyByClient[$row->client_id]))
                ? $companyByClient[$row->client_id]
                : 'Unassigned';

            $matrix[$name][$row->month_key] = ($matrix[$name][$row->month_key] ?? 0) + (float) $row->revenue;
            $bucketTotals[$name] = ($bucketTotals[$name] ?? 0) + (float) $row->revenue;
        }

        // "Unassigned" is always its own trailing series, never ranked/bucketed.
        $unassignedTotal = $bucketTotals['Unassigned'] ?? 0.0;
        unset($bucketTotals['Unassigned']);
        arsort($bucketTotals);

        $topNames = array_slice(array_keys($bucketTotals), 0, self::REVENUE_TOP_COMPANIES);
        $otherNames = array_slice(array_keys($bucketTotals), self::REVENUE_TOP_COMPANIES);

        $seriesFor = fn (string $name): array => array_map(
            static fn (string $mk) => round($matrix[$name][$mk] ?? 0, 2),
            $monthKeys
        );

        // Default (no filter) view: top N companies + "Others" + "Unassigned".
        $series = [];
        foreach ($topNames as $name) {
            $series[] = ['name' => $name, 'data' => $seriesFor($name)];
        }

        if (! empty($otherNames)) {
            $othersData = array_map(static function (string $mk) use ($otherNames, $matrix) {
                $sum = 0.0;
                foreach ($otherNames as $name) {
                    $sum += $matrix[$name][$mk] ?? 0;
                }

                return round($sum, 2);
            }, $monthKeys);

            if (array_sum($othersData) > 0) {
                $series[] = ['name' => 'Others', 'data' => $othersData];
            }
        }

        if ($unassignedTotal > 0) {
            $series[] = ['name' => 'Unassigned', 'data' => $seriesFor('Unassigned')];
        }

        // Full per-company matrix (every company as its own series, ranked desc,
        // "Unassigned" last) so the widget's company filter can select any of them,
        // including those folded into "Others" by default.
        $companies = [];
        foreach (array_keys($bucketTotals) as $name) {
            $companies[] = ['name' => $name, 'data' => $seriesFor($name)];
        }
        if ($unassignedTotal > 0) {
            $companies[] = ['name' => 'Unassigned', 'data' => $seriesFor('Unassigned')];
        }

        // Trim leading/trailing empty months once, off the default series (which
        // covers all revenue), and apply the same slice to the full company list.
        return $this->trimEmptyMonths($monthLabels, $series, $companies);
    }

    /**
     * Drop leading and trailing months where every series is zero, so the chart
     * begins at the first month that has revenue (and ends at the last). Bounds are
     * computed off $series (which covers all revenue) and the same slice is applied
     * to the full $companies list, so both share one axis.
     *
     * @param  string[]  $monthLabels
     * @param  array<int, array{name:string, data:float[]}>  $series
     * @param  array<int, array{name:string, data:float[]}>  $companies
     * @return array{months: string[], series: array, companies: array}
     */
    private function trimEmptyMonths(array $monthLabels, array $series, array $companies = []): array
    {
        $count = count($monthLabels);
        if ($count === 0 || empty($series)) {
            return ['months' => [], 'series' => [], 'companies' => []];
        }

        $first = null;
        $last = null;
        for ($i = 0; $i < $count; $i++) {
            $hasData = false;
            foreach ($series as $s) {
                if (($s['data'][$i] ?? 0) != 0.0) {
                    $hasData = true;
                    break;
                }
            }
            if ($hasData) {
                $first ??= $i;
                $last = $i;
            }
        }

        if ($first === null) {
            return ['months' => [], 'series' => [], 'companies' => []];
        }

        $length = $last - $first + 1;
        $slice = static fn (array $set): array => array_map(static fn (array $s) => [
            'name' => $s['name'],
            'data' => array_slice($s['data'], $first, $length),
        ], $set);

        return [
            'months' => array_slice($monthLabels, $first, $length),
            'series' => $slice($series),
            'companies' => $slice($companies),
        ];
    }

    /**
     * Build the two publisher time-series widgets (published-article count and
     * € spent) as website × month stacked matrices. Source is the Storages table;
     * the axis is the PUBLICATION DATE (`publication_date`) and the stack dimension
     * is the DOMAIN column (`websites.domain_name` via website_id, domain-less rows
     * bucketed as "(No domain)"). "€ spent" is the agreed publisher payment
     * (`publisher`); both are restricted to article_published rows and to the
     * selected date range (the page's date picker). Each widget shows its top
     * websites by default, or exactly the websites in its filter.
     *
     * @return array<string, mixed>
     */
    private function publisherTimeSeries(array $articleSites, array $spendSites, ?Carbon $dateFrom = null, ?Carbon $dateTo = null): array
    {
        $rows = Storage::query()
            ->where('storage.status', 'article_published')
            ->whereNotNull('storage.publication_date')
            ->when($dateFrom, fn ($q) => $q->whereDate('storage.publication_date', '>=', $dateFrom->toDateString()))
            ->when($dateTo, fn ($q) => $q->whereDate('storage.publication_date', '<=', $dateTo->toDateString()))
            ->leftJoin('websites', 'websites.id', '=', 'storage.website_id')
            ->selectRaw("DATE_FORMAT(storage.publication_date, '%Y-%m') as ym")
            ->selectRaw("COALESCE(NULLIF(websites.domain_name, ''), '(No domain)') as site")
            ->selectRaw('COUNT(*) as cnt')
            ->selectRaw('COALESCE(SUM(storage.publisher), 0) as spend')
            ->groupBy('ym', 'site')
            ->get();

        if ($rows->isEmpty()) {
            return [
                'pubArticleWidget' => ['months' => [], 'series' => []],
                'pubSpendWidget' => ['months' => [], 'series' => []],
                'pubSiteOptions' => [],
                'pubArticleSelected' => $articleSites,
                'pubSpendSelected' => $spendSites,
            ];
        }

        // Continuous month axis (min..max), gaps filled.
        $yms = $rows->pluck('ym')->unique()->sort()->values();
        $start = Carbon::createFromFormat('Y-m', $yms->first())->startOfMonth();
        $end = Carbon::createFromFormat('Y-m', $yms->last())->startOfMonth();

        $monthKeys = [];
        $monthLabels = [];
        for ($c = $start->copy(); $c->lte($end); $c->addMonth()) {
            $monthKeys[] = $c->format('Y-m');
            $monthLabels[] = $c->format('M Y');
        }

        // Per-website matrices + totals.
        $countMatrix = [];
        $spendMatrix = [];
        $countTotals = [];
        $spendTotals = [];
        foreach ($rows as $r) {
            $countMatrix[$r->site][$r->ym] = (int) $r->cnt;
            $spendMatrix[$r->site][$r->ym] = round((float) $r->spend, 2);
            $countTotals[$r->site] = ($countTotals[$r->site] ?? 0) + (int) $r->cnt;
            $spendTotals[$r->site] = ($spendTotals[$r->site] ?? 0) + (float) $r->spend;
        }

        // Selectable website list — every distinct site, most active first.
        $siteOptions = $countTotals;
        arsort($siteOptions);
        $siteOptions = array_keys($siteOptions);

        return [
            'pubArticleWidget' => $this->stackedFromMatrix($monthKeys, $monthLabels, $countMatrix, $countTotals, $articleSites, false),
            'pubSpendWidget' => $this->stackedFromMatrix($monthKeys, $monthLabels, $spendMatrix, $spendTotals, $spendSites, true),
            'pubSiteOptions' => $siteOptions,
            'pubArticleSelected' => $articleSites,
            'pubSpendSelected' => $spendSites,
        ];
    }

    /**
     * Turn a website × month matrix into a trimmed stacked-series payload. When
     * $selected is non-empty, only those websites are shown (each its own series);
     * otherwise the top N by total + an "Others" rollup, with "(No domain)" pinned
     * last (never ranked). $isMoney rounds to cents (counts stay integers).
     *
     * @return array{months: string[], series: array<int, array{name:string, data: (int|float)[]}>}
     */
    private function stackedFromMatrix(array $monthKeys, array $monthLabels, array $matrix, array $totals, array $selected, bool $isMoney): array
    {
        $seriesFor = function (string $name) use ($matrix, $monthKeys, $isMoney): array {
            return array_map(function ($mk) use ($matrix, $name, $isMoney) {
                $v = $matrix[$name][$mk] ?? 0;

                return $isMoney ? round((float) $v, 2) : (int) $v;
            }, $monthKeys);
        };

        if (! empty($selected)) {
            // Only sites that actually have data; preserve the picked order.
            $names = array_values(array_filter($selected, fn ($n) => isset($matrix[$n])));
            $series = array_map(fn ($n) => ['name' => $n, 'data' => $seriesFor($n)], $names);
        } else {
            // "(No domain)" is a data-quality bucket, not a website — keep it as a
            // trailing series, never ranked into the top sites (mirrors "Unassigned").
            $noDomain = '(No domain)';
            $hasNoDomain = isset($totals[$noDomain]);
            unset($totals[$noDomain]);

            arsort($totals);
            $topNames = array_slice(array_keys($totals), 0, self::PUBLISHER_TOP_SITES);
            $restNames = array_slice(array_keys($totals), self::PUBLISHER_TOP_SITES);

            $series = array_map(fn ($n) => ['name' => $n, 'data' => $seriesFor($n)], $topNames);

            if (! empty($restNames)) {
                $othersData = array_map(function ($mk) use ($restNames, $matrix, $isMoney) {
                    $sum = 0;
                    foreach ($restNames as $n) {
                        $sum += $matrix[$n][$mk] ?? 0;
                    }

                    return $isMoney ? round((float) $sum, 2) : (int) $sum;
                }, $monthKeys);

                if (array_sum($othersData) > 0) {
                    $series[] = ['name' => 'Others', 'data' => $othersData];
                }
            }

            if ($hasNoDomain) {
                $noDomainData = $seriesFor($noDomain);
                if (array_sum($noDomainData) > 0) {
                    $series[] = ['name' => $noDomain, 'data' => $noDomainData];
                }
            }
        }

        $trim = $this->trimEmptyMonths($monthLabels, $series);

        return ['months' => $trim['months'], 'series' => $trim['series']];
    }

    /**
     * Normalize a repeated `?param[]=` query value into a clean list of non-empty
     * website names.
     */
    private function cleanSiteParam(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map(fn ($v) => is_string($v) ? trim($v) : '', $value),
            fn ($v) => $v !== ''
        ));
    }

    private function buildMonthlySeries($rows, ?Carbon $startMonth = null, ?Carbon $endMonth = null): array
    {
        if ($rows->isEmpty() && (! $startMonth || ! $endMonth)) {
            return [];
        }

        $monthlyMap = $rows->keyBy('month_key');
        $cursor = $startMonth?->copy()
            ?? Carbon::createFromFormat('Y-m', $rows->first()->month_key)->startOfMonth();
        $end = $endMonth?->copy()
            ?? Carbon::createFromFormat('Y-m', $rows->last()->month_key)->startOfMonth();

        if ($cursor->gt($end)) {
            return [];
        }

        $series = [];

        while ($cursor->lte($end)) {
            $monthKey = $cursor->format('Y-m');
            $point = $monthlyMap->get($monthKey);

            $series[] = [
                'month' => $monthKey,
                'label' => $cursor->format('M Y'),
                'published' => $point ? (int) $point->published_count : 0,
                'profit' => $point ? (float) $point->net_profit : 0.0,
            ];

            $cursor->addMonth();
        }

        return $series;
    }

    private function applyWindow(array $monthlySeries, string $window): array
    {
        if ($window === 'all' || empty($monthlySeries)) {
            return $monthlySeries;
        }

        $limit = (int) $window;
        if ($limit <= 0) {
            return $monthlySeries;
        }

        return array_slice($monthlySeries, -$limit);
    }

    private function toQuarterlySeries(array $monthlySeries): array
    {
        if (empty($monthlySeries)) {
            return [];
        }

        $buckets = [];
        foreach ($monthlySeries as $point) {
            $monthDate = Carbon::createFromFormat('Y-m', $point['month'])->startOfMonth();
            $year = $monthDate->year;
            $quarter = (int) ceil($monthDate->month / 3);
            $bucketKey = $year.'-Q'.$quarter;

            if (! isset($buckets[$bucketKey])) {
                $buckets[$bucketKey] = [
                    'label' => 'Q'.$quarter.' '.$year,
                    'published' => 0,
                    'profit' => 0.0,
                ];
            }

            $buckets[$bucketKey]['published'] += (int) $point['published'];
            $buckets[$bucketKey]['profit'] += (float) $point['profit'];
        }

        $series = [];
        foreach ($buckets as $bucket) {
            $series[] = $bucket;
        }

        return $series;
    }

    private function buildRangeLabel(array $points, ?Carbon $dateFrom = null, ?Carbon $dateTo = null): ?string
    {
        if ($dateFrom && $dateTo) {
            return $dateFrom->format('M j, Y').' to '.$dateTo->format('M j, Y');
        }

        if ($dateFrom) {
            return 'From '.$dateFrom->format('M j, Y');
        }

        if ($dateTo) {
            return 'Up to '.$dateTo->format('M j, Y');
        }

        if (empty($points)) {
            return null;
        }

        $first = $points[0]['label'] ?? null;
        $last = $points[count($points) - 1]['label'] ?? null;

        if (! $first || ! $last) {
            return null;
        }

        return $first.' to '.$last;
    }

    private function parseSelectedDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', trim($value))->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizeSelectedDates(?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        if ($dateFrom && $dateTo && $dateFrom->gt($dateTo)) {
            return [$dateTo->copy(), $dateFrom->copy()];
        }

        return [$dateFrom, $dateTo];
    }

    private function resolveSeriesBounds(
        ?Carbon $dateFrom,
        ?Carbon $dateTo,
        ?string $minPublicationDate,
        ?string $maxPublicationDate
    ): array {
        $start = $dateFrom?->copy()->startOfMonth();
        $end = $dateTo?->copy()->startOfMonth();

        if (! $start && $dateTo) {
            $minDate = $minPublicationDate ? Carbon::parse($minPublicationDate)->startOfMonth() : null;
            $start = $minDate && $minDate->lte($end) ? $minDate : $end?->copy();
        }

        if (! $end && $dateFrom) {
            $maxDate = $maxPublicationDate ? Carbon::parse($maxPublicationDate)->startOfMonth() : null;
            $end = $maxDate && $maxDate->gte($start) ? $maxDate : $start?->copy();
        }

        if ($start && ! $end) {
            $end = $start->copy();
        }

        if ($end && ! $start) {
            $start = $end->copy();
        }

        return [$start, $end];
    }
}
