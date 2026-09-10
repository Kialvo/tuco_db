<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Working-day arithmetic for the marketplace's deadlines.
 *
 * A working day is any day that is not a Saturday or Sunday (Fabrizio,
 * 2026-09-10). Public holidays are deliberately NOT excluded: they differ by
 * country, our publishers and clients are spread across several, and a
 * calendar nobody maintains would drift into being wrong — quietly moving
 * deadlines that decide when someone's money is released.
 *
 * Deadlines are computed as an ABSOLUTE moment rather than counted backwards
 * from today, so the answer never depends on when you happen to ask, and the
 * exact date can be shown to the customer up front.
 */
class WorkingDays
{
    /**
     * The moment N working days after `$from`.
     *
     * Time of day is preserved: a Friday 14:30 start plus 3 working days is
     * Wednesday 14:30, not midnight. That matters because a deadline landing
     * at 00:00 silently shortens the last day to nothing.
     */
    public static function deadline(CarbonInterface $from, int $workingDays): Carbon
    {
        if ($workingDays < 1) {
            throw new \InvalidArgumentException('A deadline needs at least one working day.');
        }

        $date = Carbon::instance($from->toDateTime());
        $counted = 0;

        while ($counted < $workingDays) {
            $date = $date->addDay();

            if (! $date->isWeekend()) {
                $counted++;
            }
        }

        return $date;
    }

    /** Has N working days elapsed since `$from`? */
    public static function passed(CarbonInterface $from, int $workingDays, ?CarbonInterface $now = null): bool
    {
        return ($now ?? Carbon::now())->greaterThanOrEqualTo(self::deadline($from, $workingDays));
    }

    /**
     * Whole working days between two moments, for reporting.
     *
     * Counts elapsed days, so it only reaches 1 once a full working day has
     * actually gone by — an hour on a Tuesday afternoon is still 0.
     */
    public static function elapsed(CarbonInterface $from, ?CarbonInterface $to = null): int
    {
        $to = $to ?? Carbon::now();

        if ($to->lessThanOrEqualTo($from)) {
            return 0;
        }

        $cursor = Carbon::instance($from->toDateTime());
        $count = 0;

        while (true) {
            $cursor = $cursor->addDay();

            if ($cursor->greaterThan($to)) {
                break;
            }

            if (! $cursor->isWeekend()) {
                $count++;
            }
        }

        return $count;
    }
}
