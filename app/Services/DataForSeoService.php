<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DataForSeoService
{
    private const BULK_SIZE = 1000; // max targets per API call

    private function nullRow(): array
    {
        return [
            'ms'               => null,
            'organic_keywords' => null,
            'organic_traffic'  => null,
            'kw_traffic_ratio' => null,
        ];
    }

    /**
     * Fetch metrics for a single domain.
     */
    public function fetchDomain(string $domain): array
    {
        $batch = $this->fetchBatch([$domain]);
        return $batch[$domain] ?? $this->nullRow();
    }

    /**
     * Fetch metrics for multiple domains using bulk endpoints.
     * Both API calls are bulk — no timeouts even for thousands of domains.
     *
     * - MS               → backlinks/bulk_ranks             (up to 1000/call)
     * - Organic KW+TR    → dataforseo_labs/bulk_traffic_estimation (up to 1000/call)
     *
     * Returns array keyed by domain name.
     */
    public function fetchBatch(array $domains): array
    {
        if (empty($domains)) {
            return [];
        }

        $results = [];
        foreach ($domains as $d) {
            $results[$d] = $this->nullRow();
        }

        $chunks = array_chunk(array_values($domains), self::BULK_SIZE);

        // ── 1) MS via bulk_ranks ─────────────────────────────────────────────
        foreach ($chunks as $chunk) {
            try {
                $response = Http::withBasicAuth(
                    env('DATAFORSEO_LOGIN'),
                    env('DATAFORSEO_PASSWORD')
                )->timeout(60)->post(
                    'https://api.dataforseo.com/v3/backlinks/bulk_ranks/live',
                    [['targets' => $chunk]]
                );

                if (! $response->successful()) continue;

                $task = $response->json('tasks.0') ?? [];
                if (($task['status_code'] ?? 0) !== 20000) continue;

                foreach ($task['result'][0]['items'] ?? [] as $item) {
                    $d = $item['target'] ?? null;
                    if ($d && array_key_exists($d, $results)) {
                        $results[$d]['ms'] = isset($item['rank']) ? (int) $item['rank'] : null;
                    }
                }
            } catch (\Throwable $e) {
                // ms stays null for this chunk
            }
        }

        // ── 2) Organic Keywords + Traffic via bulk_traffic_estimation ────────
        foreach ($chunks as $chunk) {
            try {
                $response = Http::withBasicAuth(
                    env('DATAFORSEO_LOGIN'),
                    env('DATAFORSEO_PASSWORD')
                )->timeout(60)->post(
                    'https://api.dataforseo.com/v3/dataforseo_labs/google/bulk_traffic_estimation/live',
                    [['targets' => $chunk]]
                );

                if (! $response->successful()) continue;

                $task = $response->json('tasks.0') ?? [];
                if (($task['status_code'] ?? 0) !== 20000) continue;

                foreach ($task['result'][0]['items'] ?? [] as $item) {
                    $d = $item['target'] ?? null;
                    if (! $d || ! array_key_exists($d, $results)) continue;

                    $kw = isset($item['metrics']['organic']['count'])
                        ? (int) $item['metrics']['organic']['count'] : null;
                    $tr = isset($item['metrics']['organic']['etv'])
                        ? (int) $item['metrics']['organic']['etv']  : null;

                    $results[$d]['organic_keywords'] = $kw;
                    $results[$d]['organic_traffic']  = $tr;
                    $results[$d]['kw_traffic_ratio'] = ($tr && $kw)
                        ? round($kw / $tr, 2)
                        : 0.0;
                }
            } catch (\Throwable $e) {
                // organic data stays null for this chunk
            }
        }

        return $results;
    }
}
