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
     * Fetch metrics for multiple domains using DataForSEO Labs bulk endpoints.
     *
     * - Organic KW + Traffic → dataforseo_labs/bulk_traffic_estimation (up to 1000/call)
     * - MS score             → derived from organic ETV via log-normalisation (0–1000 scale)
     *
     * Pass $locationCode and $languageCode to get location-specific metrics instead of global.
     * Returns array keyed by the ORIGINAL domain string as passed in.
     */
    public function fetchBatch(array $domains, ?int $locationCode = null, ?string $languageCode = null): array
    {
        if (empty($domains)) {
            return [];
        }

        $results = [];
        foreach ($domains as $d) {
            $results[$d] = $this->nullRow();
        }

        $domainMap = [];
        foreach ($domains as $d) {
            $domainMap[$this->normalizeDomain($d)] = $d;
        }

        $chunks = array_chunk(array_keys($domainMap), self::BULK_SIZE);

        // ── Organic Keywords + Traffic + MS via bulk_traffic_estimation ──────
        foreach ($chunks as $chunk) {
            try {
                $payload = ['targets' => $chunk];
                if ($locationCode)  $payload['location_code']  = $locationCode;
                if ($languageCode)  $payload['language_code']  = strtolower($languageCode);

                $response = Http::withBasicAuth(
                    env('DATAFORSEO_LOGIN'),
                    env('DATAFORSEO_PASSWORD')
                )->timeout(60)->post(
                    'https://api.dataforseo.com/v3/dataforseo_labs/google/bulk_traffic_estimation/live',
                    [$payload]
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
                    $results[$orig]['kw_traffic_ratio'] = ($kw && $tr)
                        ? round($tr / $kw, 2) : null;

                    // MS: log10-normalise ETV to 0–1000 scale
                    // etv=0→0, etv=1k→~450, etv=10k→~600, etv=100k→~750, etv=10M→~1000
                    $results[$orig]['ms'] = $tr !== null
                        ? min(1000, (int) round(log10(max(1, $tr) + 1) * 150))
                        : null;
                }
            } catch (\Throwable $e) {
                // data stays null for this chunk
            }
        }

        return $results;
    }
}
