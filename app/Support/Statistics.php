<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Small, dependency-free statistics helpers used by the Storage Stats page.
 * Kept pure (no DB, no request state) so they are unit-testable in isolation.
 */
class Statistics
{
    /**
     * Median of a numeric list.
     *
     * The caller is responsible for filtering out nulls before passing values
     * in. For an even number of values the two middle values are AVERAGED (so a
     * set like [3, 4] yields 3.5), matching the Monday charts.
     *
     * @param  array<int|float>  $values
     * @return float|null null when the list is empty
     */
    public static function median(array $values): ?float
    {
        $values = array_values($values);
        $count = count($values);

        if ($count === 0) {
            return null;
        }

        sort($values, SORT_NUMERIC);

        $mid = intdiv($count, 2);

        if ($count % 2 === 1) {
            return (float) $values[$mid];
        }

        return ((float) $values[$mid - 1] + (float) $values[$mid]) / 2;
    }

    /**
     * A percentage rate, rounded to one decimal.
     *
     * Returns NULL — never 0.0 — when the denominator is zero, so a client with
     * proposals but no decision yet renders as "—" instead of a 0% that reads
     * like a real measurement.
     */
    public static function rate(int|float $part, int|float $total): ?float
    {
        if ($total <= 0) {
            return null;
        }

        return round($part / $total * 100, 1);
    }

    /**
     * Median-per-bucket series aligned to the visible (windowed) points.
     *
     * Monthly: one median per visible month (null when that month has no data).
     * Quarterly: pool the RAW values of every month in a quarter and median the
     * pool — mirroring StorageStatsController::toQuarterlySeries()' bucketing,
     * labels and first-appearance order. Never a median-of-medians and never a
     * sum (a quarter's median ≠ the sum of its months' medians).
     *
     * @param  array<int, array{month:string, label:string}>  $windowedPoints  ordered visible months (each has a 'Y-m' month key)
     * @param  array<string, array<int, int|float>>  $valuesByMonth  month_key => [values...]
     * @return array<int, float|null> aligned 1:1 to the resulting chart labels
     */
    public static function medianSeries(array $windowedPoints, array $valuesByMonth, string $granularity): array
    {
        if ($granularity === 'quarterly') {
            $buckets = [];
            foreach ($windowedPoints as $point) {
                $monthDate = Carbon::createFromFormat('Y-m', $point['month'])->startOfMonth();
                $bucketKey = $monthDate->year.'-Q'.((int) ceil($monthDate->month / 3));

                if (! isset($buckets[$bucketKey])) {
                    $buckets[$bucketKey] = [];
                }

                foreach ($valuesByMonth[$point['month']] ?? [] as $value) {
                    $buckets[$bucketKey][] = $value;
                }
            }

            return array_map(
                static fn (array $values) => static::median($values),
                array_values($buckets)
            );
        }

        return array_map(
            static fn (array $point) => static::median($valuesByMonth[$point['month']] ?? []),
            $windowedPoints
        );
    }
}
