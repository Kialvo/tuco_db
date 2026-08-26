<?php

namespace Tests\Unit;

use App\Support\MarketplaceStats;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * The Marketplace Stats pages read LIVE PRODUCTION on every request, so their
 * queries cannot be exercised in a test. These helpers are the part that can be —
 * the month axis, the alignment and the bucketing that every widget on both
 * pages depends on.
 */
class MarketplaceStatsTest extends TestCase
{
    private function now(): Carbon
    {
        return Carbon::create(2026, 8, 26, 12, 0, 0);
    }

    /* ─────────────────────────────── monthAxis ───────────────────────────── */

    public function test_month_axis_ends_at_the_current_month(): void
    {
        $axis = MarketplaceStats::monthAxis(null, $this->now());

        $this->assertSame('2026-08', array_key_last($axis));
        $this->assertSame('Aug 2026', $axis['2026-08']);
    }

    public function test_month_axis_defaults_to_a_trailing_year(): void
    {
        $axis = MarketplaceStats::monthAxis(null, $this->now());

        $this->assertCount(12, $axis);
        $this->assertSame('2025-09', array_key_first($axis));
    }

    public function test_month_axis_keeps_the_full_window_when_data_is_younger_than_it(): void
    {
        // A two-month-old marketplace still renders a year of context — two
        // columns would read as "we have no history" rather than "we are new".
        $axis = MarketplaceStats::monthAxis('2026-07', $this->now());

        $this->assertCount(12, $axis);
        $this->assertSame('2025-09', array_key_first($axis));
    }

    public function test_month_axis_extends_back_to_reach_older_data(): void
    {
        $axis = MarketplaceStats::monthAxis('2025-03', $this->now());

        $this->assertSame('2025-03', array_key_first($axis));
        $this->assertCount(18, $axis);
    }

    public function test_month_axis_is_capped_so_one_ancient_row_cannot_stretch_it(): void
    {
        $axis = MarketplaceStats::monthAxis('2015-01', $this->now());

        $this->assertCount(24, $axis);
        $this->assertSame('2024-09', array_key_first($axis));
    }

    public function test_month_axis_survives_a_future_dated_earliest_month(): void
    {
        // Clock skew or a bad row must not produce an empty chart.
        $axis = MarketplaceStats::monthAxis('2030-01', $this->now());

        $this->assertCount(12, $axis);
        $this->assertSame('2026-08', array_key_last($axis));
    }

    public function test_month_axis_ignores_a_malformed_earliest_month(): void
    {
        $this->assertSame(
            MarketplaceStats::monthAxis(null, $this->now()),
            MarketplaceStats::monthAxis('not-a-month', $this->now())
        );
    }

    /* ────────────────────────────── earliestMonth ────────────────────────── */

    public function test_earliest_month_is_the_smallest_key_across_every_map(): void
    {
        $this->assertSame('2025-11', MarketplaceStats::earliestMonth([
            ['2026-01' => 5, '2026-02' => 3],
            ['2025-11' => 1],
            [],
        ]));
    }

    public function test_earliest_month_ignores_keys_that_are_not_months(): void
    {
        $this->assertSame('2026-04', MarketplaceStats::earliestMonth([
            ['total' => 9, '2026-04' => 1],
        ]));
    }

    public function test_earliest_month_is_null_when_there_is_no_data(): void
    {
        $this->assertNull(MarketplaceStats::earliestMonth([[], []]));
    }

    /* ───────────────────────────────── align ─────────────────────────────── */

    public function test_align_projects_onto_the_axis_and_fills_gaps(): void
    {
        $axis = ['2026-01' => 'Jan 2026', '2026-02' => 'Feb 2026', '2026-03' => 'Mar 2026'];

        $this->assertSame([7, 0, 4], MarketplaceStats::align($axis, ['2026-01' => 7, '2026-03' => 4]));
    }

    public function test_align_can_fill_with_null_so_a_line_breaks_instead_of_reading_zero(): void
    {
        $axis = ['2026-01' => 'Jan 2026', '2026-02' => 'Feb 2026'];

        $this->assertSame([3.5, null], MarketplaceStats::align($axis, ['2026-01' => 3.5], null));
    }

    public function test_align_keeps_a_real_zero_distinct_from_a_missing_month(): void
    {
        $axis = ['2026-01' => 'Jan 2026', '2026-02' => 'Feb 2026'];

        // Month 1 measured zero; month 2 was never measured.
        $this->assertSame([0, null], MarketplaceStats::align($axis, ['2026-01' => 0], null));
    }

    public function test_align_drops_months_that_are_not_on_the_axis(): void
    {
        $axis = ['2026-02' => 'Feb 2026'];

        $this->assertSame([2], MarketplaceStats::align($axis, ['2026-01' => 1, '2026-02' => 2]));
    }

    /* ─────────────────────────────── cumulative ──────────────────────────── */

    public function test_cumulative_runs_a_total_across_the_series(): void
    {
        $this->assertSame([10, 30, 25], MarketplaceStats::cumulative([10, 20, -5]));
    }

    public function test_cumulative_starts_from_the_opening_balance(): void
    {
        // Without the opening balance the liability line would start at zero and
        // understate what is owed for the whole chart.
        $this->assertSame([1200, 1150], MarketplaceStats::cumulative([200, -50], 1000));
    }

    public function test_cumulative_of_an_empty_series_is_empty(): void
    {
        $this->assertSame([], MarketplaceStats::cumulative([], 500));
    }

    /* ─────────────────────────────── ageBucket ───────────────────────────── */

    public function test_age_bucket_boundaries(): void
    {
        $this->assertSame('< 1 day', MarketplaceStats::ageBucket(0.0));
        $this->assertSame('< 1 day', MarketplaceStats::ageBucket(0.99));
        $this->assertSame('1–7 days', MarketplaceStats::ageBucket(1.0));
        // 7.0 is NOT yet abandoned — the idle test is strictly greater than 7.
        $this->assertSame('1–7 days', MarketplaceStats::ageBucket(7.0));
        $this->assertSame('8–30 days', MarketplaceStats::ageBucket(7.01));
        $this->assertSame('8–30 days', MarketplaceStats::ageBucket(30.0));
        $this->assertSame('> 30 days', MarketplaceStats::ageBucket(30.01));
    }

    /* ──────────────────────────── orderCountBucket ───────────────────────── */

    public function test_order_count_bucket_labels(): void
    {
        $this->assertSame('1 order', MarketplaceStats::orderCountBucket(1));
        $this->assertSame('2 orders', MarketplaceStats::orderCountBucket(2));
        $this->assertSame('4 orders', MarketplaceStats::orderCountBucket(4));
        $this->assertSame('5+ orders', MarketplaceStats::orderCountBucket(5));
        $this->assertSame('5+ orders', MarketplaceStats::orderCountBucket(97));
    }

    public function test_a_guest_with_no_submitted_order_is_not_a_buyer(): void
    {
        $this->assertNull(MarketplaceStats::orderCountBucket(0));
    }

    /* ───────────────────────────────── tally ────────────────────────────── */

    public function test_tally_counts_into_the_declared_buckets(): void
    {
        $counts = MarketplaceStats::tally(
            ['a', 'b', 'a', 'a'],
            ['a', 'b', 'c']
        );

        $this->assertSame(['a' => 3, 'b' => 1, 'c' => 0], $counts);
    }

    public function test_tally_keeps_empty_buckets_so_the_shape_stays_comparable(): void
    {
        $counts = MarketplaceStats::tally([], MarketplaceStats::AGE_BUCKETS);

        $this->assertSame(MarketplaceStats::AGE_BUCKETS, array_keys($counts));
        $this->assertSame([0, 0, 0, 0], array_values($counts));
    }

    public function test_tally_ignores_nulls_and_unknown_labels(): void
    {
        $this->assertSame(
            ['a' => 1, 'b' => 0],
            MarketplaceStats::tally(['a', null, 'z'], ['a', 'b'])
        );
    }

    /* ─────────────────────────────── fromMinor ───────────────────────────── */

    public function test_from_minor_converts_cents_to_major_units(): void
    {
        $this->assertSame(250.0, MarketplaceStats::fromMinor(25000));
        $this->assertSame(295.55, MarketplaceStats::fromMinor(29555));
        $this->assertSame(0.0, MarketplaceStats::fromMinor(0));
    }

    /* ────────────────────────────── maturedThrough ───────────────────────── */

    public function test_matured_through_excludes_cohorts_still_inside_their_window(): void
    {
        // On 2026-08-26 the July cohort's newest signup (Jul 31) is 26 days old,
        // so July has NOT had its full 30 days; June has.
        $this->assertSame('2026-06', MarketplaceStats::maturedThrough(30, $this->now()));
    }

    public function test_matured_through_moves_forward_once_the_window_has_passed(): void
    {
        // By Sep 1 the July cohort is 32 days past its last signup, so July matured.
        $this->assertSame(
            '2026-07',
            MarketplaceStats::maturedThrough(30, Carbon::create(2026, 9, 1, 12, 0, 0))
        );
    }

    public function test_matured_through_with_a_zero_day_window_matures_last_month(): void
    {
        $this->assertSame('2026-07', MarketplaceStats::maturedThrough(0, $this->now()));
    }
}
