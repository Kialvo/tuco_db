<?php

namespace App\Http\Controllers;

class StatsController extends Controller
{
    /**
     * Database Statistics — website status split (donut) + total domains.
     * Read-only. Further metrics/charts wired in a later pass.
     */
    public function database()
    {
        // Model query auto-excludes soft-deleted (SoftDeletes); status is
        // lowercase-normalized on write, so the keys are stable slugs.
        $counts = \App\Models\Website::selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $statusChart = [
            'labels' => ['Active', 'Inactive', 'Blacklist'],
            'series' => [
                (int) ($counts['active'] ?? 0),
                (int) ($counts['inactive'] ?? 0),
                (int) ($counts['blacklist'] ?? 0),
            ],
        ];

        $totalDomains = array_sum($statusChart['series']);

        return view('stats.database', compact('statusChart', 'totalDomains'));
    }
}
