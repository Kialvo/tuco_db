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
            'domain' => 'required|string|max:255',
        ]);

        $domain = $this->normalizeDomain($request->input('domain'));

        if (empty($domain)) {
            return response()->json(['error' => 'Invalid domain.'], 422);
        }

        $payload = [[
            'target'                    => $domain,
            'mode'                      => 'one_per_domain',
            'include_subdomains'        => true,
            'exclude_internal_backlinks'=> true,
            'include_indirect_links'    => false,
            'backlinks_status_type'     => 'live',
            'rank_scale'                => 'one_thousand',
            'sort_field'                => 'rank',
            'sort_order'                => 'desc',
            'limit'                     => 200,
            'filters'                   => [['dofollow', '=', true]],
        ]];

        try {
            $response = Http::withBasicAuth(
                env('DATAFORSEO_LOGIN'),
                env('DATAFORSEO_PASSWORD')
            )->timeout(60)->post(
                'https://api.dataforseo.com/v3/backlinks/backlinks/live',
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
                $platformTypes = $item['domain_from_platform_type'] ?? [];
                return [
                    'domain'       => $item['domain_from'] ?? '',
                    'ms'           => $item['domain_from_rank'] ?? null,
                    'backlink_type'=> is_array($platformTypes)
                        ? implode(', ', $platformTypes)
                        : (string) $platformTypes,
                ];
            }, $items);

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
