<?php

namespace App\Http\Controllers\Tool;

use App\Http\Controllers\Controller;
use App\Services\DataForSeoProxyService;
use Illuminate\Http\Request;

class ReferringDomainsController extends Controller
{
    public function __construct(private DataForSeoProxyService $proxy) {}

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

        if (! $this->proxy->isConfigured()) {
            return response()->json(['error' => 'Referring domains service is not configured yet. Please add DATAFORSEO_PROXY_URL and DATAFORSEO_PROXY_SECRET to .env'], 503);
        }

        try {
            $raw  = $this->proxy->referringDomains($domain, 100);
            $task = $raw['tasks'][0] ?? [];

            if (($task['status_code'] ?? 0) !== 20000) {
                $msg = $task['status_message'] ?? 'DataForSEO API error.';
                return response()->json(['error' => $msg], 502);
            }

            $items = $task['result'][0]['items'] ?? [];

            $rows = array_map(function ($item) {
                return [
                    'domain'     => $item['domain']     ?? '',
                    'rank'       => $item['rank']        ?? null,
                    'backlinks'  => $item['backlinks']   ?? null,
                    'dofollow'   => $item['dofollow']    ?? null,
                    'first_seen' => isset($item['first_seen'])
                        ? substr($item['first_seen'], 0, 10)
                        : null,
                    'is_new'     => $item['is_new']  ?? false,
                    'is_lost'    => $item['is_lost'] ?? false,
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
