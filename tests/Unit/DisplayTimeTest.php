<?php

namespace Tests\Unit;

use App\Support\DisplayTime;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Rendering stored UTC timestamps in the team's local time.
 *
 * The bug this fixes: the progress tracker showed 13:31 for something done at
 * 15:31 in Italy. The trap in the obvious fix is hard-coding "+2" — correct in
 * summer, an hour wrong all winter. These tests pin both halves of the year.
 */
class DisplayTimeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.timezone' => 'UTC', 'app.display_timezone' => 'Europe/Rome']);
    }

    /** Summer: Italy is CEST, UTC+2. This is the reported 2-hour gap. */
    public function test_summer_timestamps_shift_by_two_hours(): void
    {
        $stored = Carbon::parse('2026-08-31 13:31:00', 'UTC');

        $this->assertSame('Aug 31, 2026 · 15:31', DisplayTime::format($stored));
    }

    /** Winter: Italy is CET, UTC+1 — a hard-coded "+2" would be wrong here. */
    public function test_winter_timestamps_shift_by_only_one_hour(): void
    {
        $stored = Carbon::parse('2026-01-15 13:31:00', 'UTC');

        $this->assertSame('Jan 15, 2026 · 14:31', DisplayTime::format($stored));
    }

    /** The zone label follows the season too, so it can never contradict the time. */
    public function test_the_zone_label_follows_the_season(): void
    {
        $this->assertSame(
            'Aug 31, 2026 · 15:31 CEST',
            DisplayTime::formatWithZone(Carbon::parse('2026-08-31 13:31:00', 'UTC'))
        );

        $this->assertSame(
            'Jan 15, 2026 · 14:31 CET',
            DisplayTime::formatWithZone(Carbon::parse('2026-01-15 13:31:00', 'UTC'))
        );
    }

    /** Conversion can push a timestamp onto the next day — date-only must follow. */
    public function test_a_late_evening_utc_timestamp_lands_on_the_next_day(): void
    {
        $stored = Carbon::parse('2026-08-31 23:10:00', 'UTC');

        $this->assertSame('Sep 1, 2026', DisplayTime::format($stored, DisplayTime::DATE_FORMAT));
    }

    /** Null in, null out — callers keep their own em-dash fallback. */
    public function test_null_passes_straight_through(): void
    {
        $this->assertNull(DisplayTime::format(null));
        $this->assertNull(DisplayTime::formatWithZone(null));
    }

    /** Nothing is mutated: the stored value must still be UTC afterwards. */
    public function test_formatting_does_not_mutate_the_original(): void
    {
        $stored = Carbon::parse('2026-08-31 13:31:00', 'UTC');

        DisplayTime::format($stored);

        $this->assertSame('13:31', $stored->format('H:i'));
        $this->assertSame('UTC', $stored->timezone->getName());
    }
}
