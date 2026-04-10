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
     * Strip protocol, www (optional), and trailing slashes so what we
     * send to the API matches what it returns as "target".
     */
    private function normalizeDomain(string $domain): string
    {
        $d = trim($domain);
        $d = preg_replace('#^https?://#i', '', $d); // strip protocol
        $d = rtrim($d, '/');                         // strip trailing slashes
        return strtolower($d);
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
     * Returns array keyed by the ORIGINAL domain string as passed in.
     */
    public function fetchBatch(array $domains): array
    {
        if (empty($domains)) {
            return [];
        }

        // Pre-fill results keyed by ORIGINAL domain string
        $results = [];
        foreach ($domains as $d) {
            $results[$d] = $this->nullRow();
        }

        // Build map: normalizedDomain → originalDomain
        // so we can match API response targets back to the original key
        $domainMap = [];
        foreach ($domains as $d) {
            $normalized = $this->normalizeDomain($d);
            // if two originals normalize to the same string, last one wins — acceptable edge case
            $domainMap[$normalized] = $d;
        }

        $normalizedDomains = array_keys($domainMap);
        $chunks = array_chunk($normalizedDomains, self::BULK_SIZE);

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
                    $target = $this->normalizeDomain($item['target'] ?? '');
                    $orig   = $domainMap[$target] ?? null;
                    if ($orig === null) continue;

                    $results[$orig]['ms'] = isset($item['rank']) ? (int) $item['rank'] : null;
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
                    $target = $this->normalizeDomain($item['target'] ?? '');
                    $orig   = $domainMap[$target] ?? null;
                    if ($orig === null) continue;

                    $kw = isset($item['metrics']['organic']['count'])
                        ? (int) $item['metrics']['organic']['count'] : null;
                    $tr = isset($item['metrics']['organic']['etv'])
                        ? (int) $item['metrics']['organic']['etv']  : null;

                    $results[$orig]['organic_keywords'] = $kw;
                    $results[$orig]['organic_traffic']  = $tr;
                    // ratio = traffic / keywords (how much traffic each keyword drives)
                    $results[$orig]['kw_traffic_ratio'] = ($kw && $tr)
                        ? round($tr / $kw, 2)
                        : null;
                }
            } catch (\Throwable $e) {
                // organic data stays null for this chunk
            }
        }

        return $results;
    }
}
