<?php

namespace App\Http\Controllers\Tool;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class KeywordResearchController extends Controller
{
    public function index()
    {
        return view('tools.keyword_research');
    }

    public function search(Request $request)
    {
        $request->validate([
            'keywords'      => 'required|string|max:2000',
            'location_name' => 'nullable|string|max:100',
            'language_name' => 'nullable|string|max:100',
            'limit'         => 'nullable|integer|min:10|max:200',
        ]);

        $login    = env('DATAFORSEO_LOGIN');
        $password = env('DATAFORSEO_PASSWORD');
        if (! $login || ! $password) {
            return response()->json(['error' => 'DataForSEO credentials not configured.'], 500);
        }

        // Parse seeds: comma or newline separated, max 5 seeds
        $seeds = array_values(array_filter(array_slice(
            array_map('trim', preg_split('/[\n,]+/', $request->input('keywords'))),
            0, 5
        )));

        if (empty($seeds)) {
            return response()->json(['error' => 'Please enter at least one keyword.'], 422);
        }

        $locationName = $request->input('location_name', 'United States');
        $languageName = $request->input('language_name', 'English');
        $limit        = (int) $request->input('limit', 100);

        // ── 1) Keyword ideas + volume + CPC ─────────────────────────────────
        $keywords = [];

        try {
            $response = Http::withBasicAuth($login, $password)
                ->timeout(60)
                ->post(
                    'https://api.dataforseo.com/v3/keywords_data/google_ads/keywords_for_keywords/live',
                    [[
                        'keywords'      => $seeds,
                        'location_name' => $locationName,
                        'language_name' => $languageName,
                        'limit'         => $limit,
                    ]]
                );

            if (! $response->successful()) {
                return response()->json(['error' => 'Keyword expansion API request failed.'], 502);
            }

            $task = $response->json('tasks.0') ?? [];

            if (($task['status_code'] ?? 0) !== 20000) {
                return response()->json(['error' => $task['status_message'] ?? 'Keyword expansion API error.'], 502);
            }

            foreach ($task['result'] ?? [] as $result) {
                foreach ($result['items'] ?? [] as $item) {
                    $kw = $item['keyword'] ?? null;
                    if (! $kw) continue;

                    $comp = $item['competition'] ?? null;
                    if (is_float($comp)) {
                        $comp = $comp < 0.34 ? 'LOW' : ($comp < 0.67 ? 'MEDIUM' : 'HIGH');
                    }

                    $keywords[$kw] = [
                        'keyword'     => $kw,
                        'volume'      => $item['search_volume'] ?? null,
                        'cpc'         => isset($item['cpc']) ? round((float) $item['cpc'], 2) : null,
                        'competition' => $comp ? strtoupper($comp) : null,
                        'kd'          => null,
                        'intent'      => null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Keyword expansion error: ' . $e->getMessage()], 500);
        }

        if (empty($keywords)) {
            return response()->json(['total' => 0, 'keywords' => []]);
        }

        $kwList = array_keys($keywords);

        // ── 2) Keyword Difficulty ────────────────────────────────────────────
        try {
            $response = Http::withBasicAuth($login, $password)
                ->timeout(60)
                ->post(
                    'https://api.dataforseo.com/v3/dataforseo_labs/google/bulk_keyword_difficulty/live',
                    [[
                        'keywords'      => $kwList,
                        'location_name' => $locationName,
                        'language_name' => $languageName,
                    ]]
                );

            if ($response->successful()) {
                $task = $response->json('tasks.0') ?? [];
                if (($task['status_code'] ?? 0) === 20000) {
                    foreach ($task['result'][0]['items'] ?? [] as $item) {
                        $kw = $item['keyword'] ?? null;
                        if ($kw && isset($keywords[$kw])) {
                            $keywords[$kw]['kd'] = isset($item['keyword_difficulty'])
                                ? (int) $item['keyword_difficulty'] : null;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // KD stays null — non-fatal, we still return results
        }

        // ── 3) Search Intent ─────────────────────────────────────────────────
        try {
            $response = Http::withBasicAuth($login, $password)
                ->timeout(60)
                ->post(
                    'https://api.dataforseo.com/v3/dataforseo_labs/google/search_intent/live',
                    [[
                        'keywords'      => $kwList,
                        'location_name' => $locationName,
                        'language_name' => $languageName,
                    ]]
                );

            if ($response->successful()) {
                $task = $response->json('tasks.0') ?? [];
                if (($task['status_code'] ?? 0) === 20000) {
                    foreach ($task['result'][0]['items'] ?? [] as $item) {
                        $kw = $item['keyword'] ?? null;
                        if ($kw && isset($keywords[$kw])) {
                            $keywords[$kw]['intent'] = $item['keyword_intent']['main_intent'] ?? null;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // intent stays null — non-fatal
        }

        return response()->json([
            'total'    => count($keywords),
            'keywords' => array_values($keywords),
        ]);
    }
}
