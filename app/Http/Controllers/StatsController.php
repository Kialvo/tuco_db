<?php

namespace App\Http\Controllers;

class StatsController extends Controller
{
    /**
     * Database Statistics — website status split (donut) + active domains.
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

        return view('stats.database', compact(
            'statusChart', 'activeDomains', 'countryChart', 'typeChart', 'languageChart'
        ));
    }
}
