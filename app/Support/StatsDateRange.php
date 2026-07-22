<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Throwable;

/**
 * Shared parsing for the Stats pages' date-range picker (?date_from / ?date_to).
 *
 * One implementation for every Stats page so the picker cannot mean one thing on
 * Production/Financial and another on Campaigns — including the "user picked the
 * dates backwards" swap, which is easy to omit in a second copy.
 *
 * Pure (no DB, no view state) so it is unit-testable in isolation.
 */
class StatsDateRange
{
    /** Parse a 'Y-m-d' query value; anything malformed or blank is simply "no bound". */
    public static function parse(mixed $value): ?Carbon
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

    /**
     * Order the two bounds. A reversed range (from > to) is swapped rather than
     * rejected — it would otherwise silently match zero rows and read as "no data".
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    public static function normalize(?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        if ($dateFrom && $dateTo && $dateFrom->gt($dateTo)) {
            return [$dateTo->copy(), $dateFrom->copy()];
        }

        return [$dateFrom, $dateTo];
    }

    /**
     * The normalized [from, to] pair for a request.
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    public static function fromRequest(Request $request): array
    {
        return static::normalize(
            static::parse($request->query('date_from')),
            static::parse($request->query('date_to'))
        );
    }
}
