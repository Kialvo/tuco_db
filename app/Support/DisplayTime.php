<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Renders a stored timestamp in the team's local time.
 *
 * Timestamps are STORED in UTC (APP_TIMEZONE) and must stay that way: this
 * database is shared with the Menford CRM, and switching the app timezone
 * would write new rows in local time while every existing row stayed UTC —
 * two incompatible meanings in one column, with no way to tell them apart
 * afterwards. Conversion therefore happens at display time only.
 *
 * A zone name, never a fixed offset. Italy is UTC+2 in summer (CEST) and
 * UTC+1 in winter (CET), so a hard-coded "+2" would be correct today and
 * quietly an hour wrong from late October — worse than the original bug,
 * because it still looks right.
 */
class DisplayTime
{
    /** Date + 24h clock, matching what the order screens already used. */
    public const DEFAULT_FORMAT = 'M j, Y · H:i';

    /** Date only, for list views where the clock is noise. */
    public const DATE_FORMAT = 'M j, Y';

    public static function zone(): string
    {
        return config('app.display_timezone') ?: 'Europe/Rome';
    }

    /**
     * Localised timestamp, e.g. "Aug 31, 2026 · 15:31".
     * Null in, null out, so callers can keep their own em-dash fallback.
     */
    public static function format(?DateTimeInterface $when, string $format = self::DEFAULT_FORMAT): ?string
    {
        if ($when === null) {
            return null;
        }

        return Carbon::instance($when)->setTimezone(self::zone())->format($format);
    }

    /**
     * Same, with the zone spelled out: "Aug 31, 2026 · 15:31 CEST".
     *
     * Worth the extra characters wherever someone checks a timestamp against
     * their own clock — the whole point is that the reader can tell which
     * clock they are looking at.
     */
    public static function formatWithZone(?DateTimeInterface $when, string $format = self::DEFAULT_FORMAT): ?string
    {
        return self::format($when, $format.' T');
    }

    /** Current abbreviation for the display zone: CEST in summer, CET in winter. */
    public static function abbreviation(): string
    {
        return Carbon::now(self::zone())->format('T');
    }
}
