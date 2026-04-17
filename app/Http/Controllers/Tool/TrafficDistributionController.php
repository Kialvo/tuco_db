<?php

namespace App\Http\Controllers\Tool;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TrafficDistributionController extends Controller
{
    public function index()
    {
        return view('tools.traffic_distribution');
    }

    public function search(Request $request)
    {
        $request->validate([
            'domains'  => 'nullable|string|max:10000',
            'csv_file' => 'nullable|file|mimes:csv,txt|max:512',
        ]);

        // ── Collect domains from textarea ──
        $rawLines = [];

        if ($request->filled('domains')) {
            $rawLines = preg_split('/[\r\n]+/', trim($request->input('domains')));
        }

        // ── Collect domains from CSV upload ──
        if ($request->hasFile('csv_file')) {
            $lines = file($request->file('csv_file')->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $cols = str_getcsv($line);
                $cell = trim($cols[0] ?? '');
                // skip header row
                if (in_array(strtolower($cell), ['domain', 'url', 'website', 'site'], true)) {
                    continue;
                }
                $rawLines[] = $cell;
            }
        }

        // ── Normalize & deduplicate ──
        $domains = [];
        foreach ($rawLines as $line) {
            $d = $this->normalizeDomain($line);
            if ($d !== '' && !in_array($d, $domains, true)) {
                $domains[] = $d;
            }
        }

        if (empty($domains)) {
            return response()->json(['error' => 'Please enter at least one domain.'], 422);
        }

        if (count($domains) > 50) {
            return response()->json(['error' => 'Maximum 50 domains per request.'], 422);
        }

        $results = [];

        foreach ($domains as $domain) {
            $results[] = $this->fetchDomain($domain);
        }

        return response()->json(['results' => $results]);
    }

    // ────────────────────────────────────────────────────────────
    private function fetchDomain(string $domain): array
    {
        try {
            $response = Http::withBasicAuth(
                env('DATAFORSEO_LOGIN'),
                env('DATAFORSEO_PASSWORD')
            )->timeout(60)->post(
                'https://api.dataforseo.com/v3/dataforseo_labs/google/domain_rank_overview/live',
                [['target' => $domain]]
            );

            if (!$response->successful()) {
                return ['domain' => $domain, 'error' => 'API request failed (' . $response->status() . ').', 'countries' => []];
            }

            $task = $response->json('tasks.0') ?? [];

            if (($task['status_code'] ?? 0) !== 20000) {
                $msg = $task['status_message'] ?? 'Unknown API error.';
                return ['domain' => $domain, 'error' => $msg, 'countries' => []];
            }

            $items = $task['result'][0]['items'] ?? [];

            if (empty($items)) {
                return ['domain' => $domain, 'countries' => []];
            }

            // ── Aggregate etv per location ──
            $byLocation = [];
            foreach ($items as $item) {
                $code = $item['location_code'] ?? null;
                $etv  = $item['metrics']['organic']['etv'] ?? 0;
                if ($code === null) continue;
                $byLocation[$code] = ($byLocation[$code] ?? 0) + (float) $etv;
            }

            $globalTotal = array_sum($byLocation);

            if ($globalTotal <= 0) {
                return ['domain' => $domain, 'countries' => []];
            }

            // Sort descending by etv
            arsort($byLocation);

            $countries = [];
            foreach (array_slice($byLocation, 0, 3, true) as $code => $etv) {
                $countries[] = [
                    'name' => $this->locationName((int) $code),
                    'pct'  => round($etv / $globalTotal * 100, 1),
                ];
            }

            return ['domain' => $domain, 'countries' => $countries];

        } catch (\Throwable $e) {
            return ['domain' => $domain, 'error' => $e->getMessage(), 'countries' => []];
        }
    }

    // ────────────────────────────────────────────────────────────
    private function normalizeDomain(string $domain): string
    {
        $d = trim($domain);
        if ($d === '') return '';
        $d = preg_replace('#^https?://#i', '', $d);
        $d = rtrim($d, '/');
        // strip path
        $d = explode('/', $d)[0];
        return strtolower($d);
    }

    // ────────────────────────────────────────────────────────────
    private function locationName(int $code): string
    {
        static $map = [
            2004 => 'Afghanistan',
            2008 => 'Albania',
            2012 => 'Algeria',
            2024 => 'Angola',
            2032 => 'Argentina',
            2036 => 'Australia',
            2040 => 'Austria',
            2031 => 'Azerbaijan',
            2048 => 'Bahrain',
            2050 => 'Bangladesh',
            2051 => 'Belarus',
            2056 => 'Belgium',
            2068 => 'Bolivia',
            2070 => 'Bosnia and Herzegovina',
            2076 => 'Brazil',
            2096 => 'Brunei',
            2100 => 'Bulgaria',
            2116 => 'Cambodia',
            2120 => 'Cameroon',
            2124 => 'Canada',
            2144 => 'Sri Lanka',
            2152 => 'Chile',
            2156 => 'China',
            2170 => 'Colombia',
            2188 => 'Costa Rica',
            2191 => 'Croatia',
            2192 => 'Cuba',
            2196 => 'Cyprus',
            2203 => 'Czech Republic',
            2208 => 'Denmark',
            2214 => 'Dominican Republic',
            2218 => 'Ecuador',
            2818 => 'Egypt',
            2222 => 'El Salvador',
            2233 => 'Estonia',
            2231 => 'Ethiopia',
            2246 => 'Finland',
            2250 => 'France',
            2276 => 'Germany',
            2288 => 'Ghana',
            2300 => 'Greece',
            2320 => 'Guatemala',
            2332 => 'Haiti',
            2340 => 'Honduras',
            2344 => 'Hong Kong',
            2348 => 'Hungary',
            2356 => 'India',
            2360 => 'Indonesia',
            2368 => 'Iraq',
            2372 => 'Ireland',
            2376 => 'Israel',
            2380 => 'Italy',
            2384 => 'Ivory Coast',
            2388 => 'Jamaica',
            2392 => 'Japan',
            2400 => 'Jordan',
            2398 => 'Kazakhstan',
            2404 => 'Kenya',
            2410 => 'South Korea',
            2414 => 'Kuwait',
            2418 => 'Laos',
            2428 => 'Latvia',
            2422 => 'Lebanon',
            2440 => 'Lithuania',
            2458 => 'Malaysia',
            2470 => 'Malta',
            2484 => 'Mexico',
            2498 => 'Moldova',
            2504 => 'Morocco',
            2508 => 'Mozambique',
            2104 => 'Myanmar',
            2524 => 'Nepal',
            2528 => 'Netherlands',
            2554 => 'New Zealand',
            2558 => 'Nicaragua',
            2566 => 'Nigeria',
            2578 => 'Norway',
            2586 => 'Pakistan',
            2591 => 'Panama',
            2600 => 'Paraguay',
            2604 => 'Peru',
            2688 => 'Serbia',
            2702 => 'Singapore',
            2608 => 'Philippines',
            2616 => 'Poland',
            2620 => 'Portugal',
            2630 => 'Puerto Rico',
            2634 => 'Qatar',
            2642 => 'Romania',
            2643 => 'Russia',
            2682 => 'Saudi Arabia',
            2686 => 'Senegal',
            2694 => 'Sierra Leone',
            2703 => 'Slovakia',
            2705 => 'Slovenia',
            2710 => 'South Africa',
            2724 => 'Spain',
            2752 => 'Sweden',
            2756 => 'Switzerland',
            2158 => 'Taiwan',
            2764 => 'Thailand',
            2788 => 'Tunisia',
            2792 => 'Turkey',
            2800 => 'Uganda',
            2804 => 'Ukraine',
            2784 => 'United Arab Emirates',
            2826 => 'United Kingdom',
            2840 => 'United States',
            2858 => 'Uruguay',
            2862 => 'Venezuela',
            2704 => 'Vietnam',
            2887 => 'Yemen',
            2716 => 'Zimbabwe',
        ];

        return $map[$code] ?? "Unknown ($code)";
    }
}
