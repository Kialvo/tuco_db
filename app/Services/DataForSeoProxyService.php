<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DataForSeoProxyService
{
    private string $url;
    private string $secret;

    public function __construct()
    {
        $this->url    = config('services.dataforseo_proxy.url', '');
        $this->secret = config('services.dataforseo_proxy.secret', '');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->url) && ! empty($this->secret);
    }

    /**
     * Call the Apps Script proxy with a given action and DataForSEO payload.
     *
     * @param  string  $action   'referring_domains' | 'domain_summary'
     * @param  array   $payload  The DataForSEO request payload array
     * @return array             Parsed JSON response from DataForSEO (via proxy)
     *
     * @throws \RuntimeException on HTTP or API failure
     */
    public function call(string $action, array $payload): array
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('DataForSEO proxy is not configured. Set DATAFORSEO_PROXY_URL and DATAFORSEO_PROXY_SECRET in .env');
        }

        $response = Http::timeout(60)->post($this->url, [
            'secret'  => $this->secret,
            'action'  => $action,
            'payload' => $payload,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Proxy request failed with status ' . $response->status());
        }

        return $response->json() ?? [];
    }

    /**
     * Fetch referring domains for a target domain.
     *
     * @param  string  $target  e.g. "example.com"
     * @param  int     $limit
     * @return array
     */
    public function referringDomains(string $target, int $limit = 100): array
    {
        return $this->call('referring_domains', [[
            'target'   => $target,
            'limit'    => $limit,
            'order_by' => ['rank,desc'],
        ]]);
    }

    /**
     * Fetch backlink summary for a target domain.
     *
     * @param  string  $target
     * @return array
     */
    public function domainSummary(string $target): array
    {
        return $this->call('domain_summary', [[
            'target' => $target,
        ]]);
    }
}
