<?php

namespace Tests\Unit;

use App\Support\WorkingDays;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Weekends only — no public holidays (Fabrizio, 2026-09-10).
 *
 * These deadlines decide when a customer's money is released and when an
 * article is published without their reply, so the arithmetic is worth
 * pinning down precisely rather than trusting to intuition about weekends.
 */
class WorkingDaysTest extends TestCase
{
    /** 2026-09-07 is a Monday, which makes the rest of these readable. */
    private function monday(string $time = '10:00'): Carbon
    {
        return Carbon::parse('2026-09-07 '.$time);
    }

    public function test_a_midweek_deadline_is_simple_addition(): void
    {
        // Monday + 3 working days = Thursday
        $this->assertSame(
            '2026-09-10',
            WorkingDays::deadline($this->monday(), 3)->toDateString()
        );
    }

    /** The whole point: Saturday and Sunday do not count. */
    public function test_a_deadline_skips_the_weekend(): void
    {
        // Thursday + 3 working days = Tuesday, not Sunday
        $thursday = Carbon::parse('2026-09-10 10:00');

        $this->assertSame(
            '2026-09-15',
            WorkingDays::deadline($thursday, 3)->toDateString()
        );
    }

    /** Five working days from Monday is the following Monday. */
    public function test_five_working_days_from_monday_is_the_next_monday(): void
    {
        $this->assertSame(
            '2026-09-14',
            WorkingDays::deadline($this->monday(), 5)->toDateString()
        );
    }

    /** A clock started at the weekend begins on Monday. */
    public function test_a_deadline_starting_on_a_saturday_counts_from_monday(): void
    {
        $saturday = Carbon::parse('2026-09-12 10:00');

        $this->assertSame(
            '2026-09-14',
            WorkingDays::deadline($saturday, 1)->toDateString()
        );
    }

    /**
     * Time of day is preserved. A deadline that snapped to midnight would
     * silently rob the customer of the final day.
     */
    public function test_the_time_of_day_survives(): void
    {
        $deadline = WorkingDays::deadline($this->monday('14:30'), 3);

        $this->assertSame('14:30', $deadline->format('H:i'));
    }

    public function test_a_deadline_needs_at_least_one_day(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        WorkingDays::deadline($this->monday(), 0);
    }

    /* ─────────────────────────── passed() ─────────────────────────── */

    public function test_nothing_has_passed_before_the_deadline(): void
    {
        $start = $this->monday();

        $this->assertFalse(WorkingDays::passed($start, 3, Carbon::parse('2026-09-10 09:59')));
    }

    public function test_the_deadline_passes_exactly_on_time(): void
    {
        $start = $this->monday();

        $this->assertTrue(WorkingDays::passed($start, 3, Carbon::parse('2026-09-10 10:00')));
    }

    /**
     * A Friday start must not expire over the weekend — this is the case that
     * would quietly release someone's tokens two days early.
     */
    public function test_a_friday_start_does_not_expire_on_sunday(): void
    {
        $friday = Carbon::parse('2026-09-11 10:00');

        $this->assertFalse(WorkingDays::passed($friday, 2, Carbon::parse('2026-09-13 23:59')));
        $this->assertTrue(WorkingDays::passed($friday, 2, Carbon::parse('2026-09-15 10:00')));
    }

    /* ─────────────────────────── elapsed() ─────────────────────────── */

    public function test_an_hour_is_not_a_working_day(): void
    {
        $this->assertSame(0, WorkingDays::elapsed($this->monday(), $this->monday('16:00')));
    }

    public function test_elapsed_counts_only_weekdays(): void
    {
        // Friday to the following Monday: Saturday and Sunday do not count.
        $friday = Carbon::parse('2026-09-11 10:00');
        $monday = Carbon::parse('2026-09-14 10:00');

        $this->assertSame(1, WorkingDays::elapsed($friday, $monday));
    }

    public function test_a_full_week_is_five_working_days(): void
    {
        $this->assertSame(
            5,
            WorkingDays::elapsed($this->monday(), Carbon::parse('2026-09-14 10:00'))
        );
    }

    public function test_elapsed_never_goes_negative(): void
    {
        $this->assertSame(0, WorkingDays::elapsed($this->monday(), $this->monday('09:00')));
    }
}
