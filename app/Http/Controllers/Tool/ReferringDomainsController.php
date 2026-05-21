<?php

namespace App\Http\Controllers\Tool;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ReferringDomainsController extends Controller
{
    public function index()
    {
        return view('tools.referring_domains');
    }

    public function search(Request $request)
    {
        $request->validate([
            'domain'        => 'required|string|max:255',
            'location_name' => 'nullable|string|max:100',
        ]);

        $domain       = $this->normalizeDomain($request->input('domain'));
        $locationName = $request->input('location_name', 'United States');

        if (empty($domain)) {
            return response()->json(['error' => 'Invalid domain.'], 422);
        }

        $login    = env('DATAFORSEO_LOGIN');
        $password = env('DATAFORSEO_PASSWORD');
        if (! $login || ! $password) {
            return response()->json(['error' => 'DataForSEO credentials not configured.'], 500);
        }

        $languageMap = [
            'United States'  => 'English',
            'United Kingdom' => 'English',
            'Australia'      => 'English',
            'Canada'         => 'English',
            'India'          => 'English',
            'Italy'          => 'Italian',
            'Spain'          => 'Spanish',
            'Mexico'         => 'Spanish',
            'Argentina'      => 'Spanish',
            'France'         => 'French',
            'Germany'        => 'German',
            'Netherlands'    => 'Dutch',
            'Denmark'        => 'Danish',
            'Sweden'         => 'Swedish',
            'Norway'         => 'Norwegian',
            'Brazil'         => 'Portuguese',
            'Russia'         => 'Russian',
            'Poland'         => 'Polish',
        ];
        $languageName = $languageMap[$locationName] ?? 'English';

        $payload = [[
            'target'        => $domain,
            'location_name' => $locationName,
            'language_name' => $languageName,
            'limit'         => 100,
        ]];

        try {
            $response = Http::withBasicAuth($login, $password)
                ->timeout(60)
                ->post(
                    'https://api.dataforseo.com/v3/dataforseo_labs/google/competitors_domain/live',
                    $payload
                );

            if (! $response->successful()) {
                return response()->json(['error' => 'API request failed.'], 502);
            }

            $task = $response->json('tasks.0') ?? [];

            if (($task['status_code'] ?? 0) !== 20000) {
                $msg = $task['status_message'] ?? 'Unknown API error.';
                return response()->json(['error' => $msg], 502);
            }

            $items = $task['result'][0]['items'] ?? [];

            $rows = array_map(function ($item) {
                $etv   = $item['full_domain_metrics']['organic']['etv']   ?? null;
                $count = $item['full_domain_metrics']['organic']['count'] ?? null;
                $ms    = $etv !== null
                    ? min(1000, (int) round(
                        log10(max(1, (float)($count ?? 0)) + 1) * 120 +
                        log10(max(1, (float) $etv) + 1) * 60
                    ))
                    : null;
                return [
                    'domain'          => $item['domain'] ?? '',
                    'intersections'   => $item['intersections'] ?? null,
                    'relevance'       => isset($item['competitor_relevance'])
                        ? round($item['competitor_relevance'] * 100, 1)
                        : null,
                    'ms'              => $ms,
                    'organic_traffic' => $etv !== null ? (int) $etv : null,
                ];
            }, $items);


            // Sort by shared keywords descending
            usort($rows, fn ($a, $b) => ($b['intersections'] ?? 0) <=> ($a['intersections'] ?? 0));

            return response()->json([
                'domain' => $domain,
                'total'  => count($rows),
                'rows'   => $rows,
            ]);

        } catch (\Throwable $e) {
            return response()->json(['error' => 'Request error: ' . $e->getMessage()], 500);
        }
    }

    private function normalizeDomain(string $domain): string
    {
        $d = trim($domain);
        $d = preg_replace('#^https?://#i', '', $d);
        $d = rtrim($d, '/');
        return strtolower($d);
    }
}
