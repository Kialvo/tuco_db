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

    public function index(Request $request)
    {
        return view('storages.stats', $this->computeStats($request));
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

        return [
            'labels' => $labels,
            'publishedSeries' => $publishedSeries,
            'guestPostsMonthly' => $guestPostsMonthly,
            'netProfitMonthly' => $netProfitMonthly,
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
