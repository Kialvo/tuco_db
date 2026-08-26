<?php

namespace App\Support;

use Carbon\Carbon;
use Throwable;

/**
 * Pure shaping helpers for the Marketplace Stats pages.
 *
 * Deliberately dependency-free (no DB, no request state, no Eloquent) so the
 * month-axis and series-alignment logic is unit-testable without a database —
 * which matters more here than on the Link-Building pages: every configured DB
 * connection in this project points at LIVE PRODUCTION, so these helpers are
 * the only part of the marketplace KPIs that can be exercised in isolation.
 *
 * @see \App\Http\Controllers\MarketplaceStatsController
 */
class MarketplaceStats
{
    /** Cart-age buckets, in render order. Also the labels ageBucket() returns. */
    public const AGE_BUCKETS = ['< 1 day', '1–7 days', '8–30 days', '> 30 days'];

    /** Orders-per-buyer buckets, in render order. Also what orderCountBucket() returns. */
    public const ORDER_COUNT_BUCKETS = ['1 order', '2 orders', '3 orders', '4 orders', '5+ orders'];

    /**
     * The month axis every trend widget on a Marketplace page shares.
     *
     * Rules, in order:
     *   • ends at the CURRENT month (these pages have no date-range picker —
     *     see the class docblock on MarketplaceStatsController for why);
     *   • starts $trailing months back, so a marketplace with two months of
     *     data still renders a full year of context instead of two columns;
     *   • extends FURTHER back when older data exists, so history is never
     *     silently cropped;
     *   • but never spans more than $maxMonths, so one very old row cannot
     *     stretch the axis into an unreadable strip.
     *
     * @param  string|null  $earliest  earliest data month as 'Y-m', or null when there is none
     * @return array<string, string> 'Y-m' => 'M Y', chronological
     */
    public static function monthAxis(
        ?string $earliest,
        ?Carbon $now = null,
        int $trailing = 12,
        int $maxMonths = 24
    ): array {
        $end = ($now ? $now->copy() : Carbon::now())->startOfMonth();
        $start = $end->copy()->subMonths(max(0, $trailing - 1));

        $earliestMonth = static::parseMonth($earliest);
        if ($earliestMonth && $earliestMonth->lt($start)) {
            $start = $earliestMonth;
        }

        $cap = $end->copy()->subMonths(max(0, $maxMonths - 1));
        if ($start->lt($cap)) {
            $start = $cap;
        }

        // A future-dated "earliest" (clock skew, bad row) must not produce an
        // empty axis — collapse to the current month rather than render nothing.
        if ($start->gt($end)) {
            $start = $end->copy();
        }

        $axis = [];
        for ($m = $start->copy(); $m->lte($end); $m->addMonth()) {
            $axis[$m->format('Y-m')] = $m->format('M Y');
        }

        return $axis;
    }

    /**
     * The smallest 'Y-m' across several month-keyed maps — the axis' natural start.
     *
     * @param  array<int, array<string, mixed>>  $maps  month-keyed maps
     */
    public static function earliestMonth(array $maps): ?string
    {
        $keys = [];
        foreach ($maps as $map) {
            foreach (array_keys($map) as $key) {
                if (is_string($key) && preg_match('/^\d{4}-\d{2}$/', $key)) {
                    $keys[] = $key;
                }
            }
        }

        return $keys ? min($keys) : null;
    }

    /**
     * Project a month-keyed map onto the axis, in axis order.
     *
     * Missing months take $fill. Pass null as $fill for a metric where "no data"
     * is NOT zero (a median, a rate) — ApexCharts breaks the line at a null,
     * which is the honest rendering; a 0 would read as a measured floor.
     *
     * @param  array<string, string>  $axis  from monthAxis()
     * @param  array<string, mixed>  $byMonth  'Y-m' => value
     * @return array<int, mixed>
     */
    public static function align(array $axis, array $byMonth, mixed $fill = 0): array
    {
        $out = [];
        foreach (array_keys($axis) as $ym) {
            $out[] = array_key_exists($ym, $byMonth) ? $byMonth[$ym] : $fill;
        }

        return $out;
    }

    /**
     * Running total of a series, starting from $opening.
     *
     * Used for the token-liability line: the ledger stores movements, so the
     * balance at the end of each month is the opening balance plus every
     * movement since. $opening carries the pre-axis history, without which the
     * line would start at zero and understate the liability for the whole chart.
     *
     * @param  array<int, int|float>  $values
     * @return array<int, int|float>
     */
    public static function cumulative(array $values, int|float $opening = 0): array
    {
        $running = $opening;
        $out = [];

        foreach ($values as $value) {
            $running += $value;
            $out[] = $running;
        }

        return $out;
    }

    /**
     * Which AGE_BUCKETS bucket a cart age in days falls into.
     *
     * Boundaries are inclusive at the top (7.0 days is "1–7 days", not "8–30"),
     * so a cart idle exactly at the 7-day abandonment threshold is not yet
     * counted as abandoned — matching idleShare()'s strictly-greater test.
     */
    public static function ageBucket(float $days): string
    {
        if ($days < 1) {
            return self::AGE_BUCKETS[0];
        }

        if ($days <= 7) {
            return self::AGE_BUCKETS[1];
        }

        if ($days <= 30) {
            return self::AGE_BUCKETS[2];
        }

        return self::AGE_BUCKETS[3];
    }

    /**
     * Which ORDER_COUNT_BUCKETS bucket a lifetime submitted-order count falls into.
     * Anything below 1 is not a buyer and has no bucket.
     */
    public static function orderCountBucket(int $orders): ?string
    {
        if ($orders < 1) {
            return null;
        }

        return self::ORDER_COUNT_BUCKETS[min($orders, 5) - 1];
    }

    /**
     * Tally a list of bucket labels into a count per bucket, in $buckets order.
     *
     * Buckets with no members are KEPT at zero: a distribution that silently
     * drops its empty buckets re-scales between page loads and stops being
     * comparable month to month.
     *
     * @param  array<int, string|null>  $labels
     * @param  array<int, string>  $buckets
     * @return array<string, int>
     */
    public static function tally(array $labels, array $buckets): array
    {
        $counts = array_fill_keys($buckets, 0);

        foreach ($labels as $label) {
            if ($label !== null && array_key_exists($label, $counts)) {
                $counts[$label]++;
            }
        }

        return $counts;
    }

    /**
     * Minor currency units (cents) as a major-unit float. Integers in, float out —
     * money never travels as a float, it only arrives as one for display.
     */
    public static function fromMinor(int|float $minor): float
    {
        return round($minor / 100, 2);
    }

    /**
     * The last month a "converted within N days of signup" cohort can be judged on.
     *
     * A cohort is only final once every member has had the full window: the
     * newest signup in month M is dated M-end, so the cohort matures N days
     * after that. Cohorts after this month are still moving and must be
     * labelled as such rather than read as a drop in conversion.
     *
     * @return string|null 'Y-m', or null when even the oldest visible cohort is immature
     */
    public static function maturedThrough(int $windowDays, ?Carbon $now = null): ?string
    {
        $today = ($now ? $now->copy() : Carbon::now())->startOfDay();
        $month = $today->copy()->startOfMonth();

        // Walk back until the month's LAST day plus the window has passed.
        for ($i = 0; $i < 120; $i++) {
            $candidate = $month->copy()->subMonths($i);
            if ($candidate->copy()->endOfMonth()->addDays($windowDays)->lte($today)) {
                return $candidate->format('Y-m');
            }
        }

        return null;
    }

    /** Parse a 'Y-m' key; anything malformed is simply "no month". */
    private static function parseMonth(?string $month): ?Carbon
    {
        if (! is_string($month) || ! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (Throwable) {
            return null;
        }
    }
}
