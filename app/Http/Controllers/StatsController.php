<?php

namespace App\Http\Controllers;

class StatsController extends Controller
{
    /**
     * Database Statistics — website status split (donut) + active domains.
     * Read-only. Further metrics/charts wired in a later pass.
     */
    public function publishers()
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

        $activeDomains = (int) ($counts['active'] ?? 0);

        // Active websites by country — top 10 + "Other" (81 distinct countries
        // is too many slices for a readable pie). Country_id is nullable, so
        // null-country actives are folded into "Other" to keep totals honest.
        $countryRows = \App\Models\Website::query()
            ->where('websites.status', 'active')
            ->join('countries', 'websites.country_id', '=', 'countries.id')
            ->selectRaw('countries.country_name as name, COUNT(*) as c')
            ->groupBy('countries.country_name')
            ->orderByDesc('c')
            ->pluck('c', 'name');

        $topCountries = $countryRows->take(10);
        $otherTotal = (int) $countryRows->slice(10)->sum()
            + \App\Models\Website::where('status', 'active')->whereNull('country_id')->count();

        $countryLabels = $topCountries->keys()->all();
        $countrySeries = $topCountries->map(fn ($v) => (int) $v)->values()->all();

        if ($otherTotal > 0) {
            $countryLabels[] = 'Other';
            $countrySeries[] = $otherTotal;
        }

        $countryChart = ['labels' => $countryLabels, 'series' => $countrySeries];

        // Active websites by type — 5 clean buckets, empty/null folded to "(None)".
        $typeRows = \App\Models\Website::query()
            ->where('status', 'active')
            ->selectRaw('COALESCE(NULLIF(type_of_website, ""), "(None)") as name, COUNT(*) as c')
            ->groupBy('name')
            ->orderByDesc('c')
            ->pluck('c', 'name');

        $typeChart = [
            'labels' => $typeRows->keys()->all(),
            'series' => $typeRows->map(fn ($v) => (int) $v)->values()->all(),
        ];

        // Active websites by language — top 10 + "Other" (23 distinct), null
        // languages folded into "Other" to keep the total honest.
        $langRows = \App\Models\Website::query()
            ->where('websites.status', 'active')
            ->join('languages', 'websites.language_id', '=', 'languages.id')
            ->selectRaw('languages.name as name, COUNT(*) as c')
            ->groupBy('languages.name')
            ->orderByDesc('c')
            ->pluck('c', 'name');

        $topLang = $langRows->take(10);
        $otherLang = (int) $langRows->slice(10)->sum()
            + \App\Models\Website::where('status', 'active')->whereNull('language_id')->count();

        $langLabels = $topLang->keys()->all();
        $langSeries = $topLang->map(fn ($v) => (int) $v)->values()->all();

        if ($otherLang > 0) {
            $langLabels[] = 'Other';
            $langSeries[] = $otherLang;
        }

        $languageChart = ['labels' => $langLabels, 'series' => $langSeries];

        return view('stats.publishers', compact(
            'statusChart', 'activeDomains', 'countryChart', 'typeChart', 'languageChart'
        ));
    }

    /**
     * Campaigns Statistics — completed campaigns per month, stacked by the
     * completion status (the config "Completed" group). Read-only.
     */
    public function campaigns()
    {
        // Ordered list of "Completed" statuses (single source of truth: config).
        $completedStatuses = config('linkbuilding.campaign_statuses.Completed', []);

        // Fixed colour per status, echoing the screenshot's deadline-respect
        // coding (in-time green, our-fault red, publisher gold, etc.).
        $statusColors = [
            'Completed in time' => '#10b981', // emerald
            'Completed with delay (Our fault)' => '#ef4444', // red
            'Completed with Delay – Late Budget Approval' => '#f59e0b', // amber
            'Completed with Delay – Sites Added Mid-Campaign' => '#ec4899', // pink
            'Completed with Delay – Client Unresponsive' => '#64748b', // slate
            "Completed with Delay – Publisher's Fault" => '#eab308', // gold
        ];

        // Completed campaigns bucketed by completion month + status. Rows with
        // no completion_date can't be placed on the month axis, so they're
        // excluded (SoftDeletes auto-excludes trashed campaigns).
        $rows = \App\Models\Campaign::query()
            ->whereIn('status', $completedStatuses)
            ->whereNotNull('completion_date')
            ->selectRaw("DATE_FORMAT(completion_date, '%Y-%m') as ym, status, COUNT(*) as c")
            ->groupBy('ym', 'status')
            ->get();

        // Month axis: trailing 12 months, extended back if older data exists.
        $end = \Carbon\Carbon::now()->startOfMonth();
        $start = $end->copy()->subMonths(11);
        $earliest = $rows->min('ym');
        if ($earliest) {
            $earliestC = \Carbon\Carbon::createFromFormat('Y-m', $earliest)->startOfMonth();
            if ($earliestC->lt($start)) {
                $start = $earliestC;
            }
        }

        $months = []; // ['Y-m' => 'Mon YYYY']
        for ($m = $start->copy(); $m->lte($end); $m->addMonth()) {
            $months[$m->format('Y-m')] = $m->format('M Y');
        }

        // [ym][status] => count
        $lookup = [];
        foreach ($rows as $r) {
            $lookup[$r->ym][$r->status] = (int) $r->c;
        }

        // One series per status that actually occurs in-window (config order).
        $series = [];
        $colors = [];
        foreach ($completedStatuses as $status) {
            $data = [];
            $total = 0;
            foreach (array_keys($months) as $ym) {
                $v = $lookup[$ym][$status] ?? 0;
                $data[] = $v;
                $total += $v;
            }
            if ($total > 0) {
                $series[] = ['name' => $status, 'data' => $data];
                $colors[] = $statusColors[$status] ?? '#94a3b8';
            }
        }

        $campaignsChart = [
            'categories' => array_values($months),
            'series' => $series,
            'colors' => $colors,
            'totalCompleted' => array_sum(array_map(fn ($s) => array_sum($s['data']), $series)),
        ];

        return view('stats.campaigns', compact('campaignsChart'));
    }
}
