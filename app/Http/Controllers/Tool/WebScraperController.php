<?php

namespace App\Http\Controllers\Tool;

use App\Http\Controllers\Controller;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class WebScraperController extends Controller
{
    /* ----------  POST /tools/discover/search  ---------- */
    public function search(Request $request)
    {
        $data = $request->validate([
            // either “query” or “tld” must be given
            'query'           => 'nullable|string|max:255|required_without:tld',
            'tld'             => 'nullable|string|max:10|required_without:query',
            'language'        => 'nullable|alpha|size:2',
            'exclude_gov_edu' => 'sometimes|boolean',
        ]);

        $limit = 100;

        // 1) build the SERP query
        $pieces = [];
        if (! empty($data['query'])) {
            $pieces[] = $data['query'];
        }
        if (! empty($data['tld'])) {
            $dotless  = ltrim(Str::lower($data['tld']), '.');
            $pieces[] = 'site:.' . $dotless;
        }
        $searchString = implode(' ', $pieces);

        // 2) fetch up to 100 URLs
        $urls = $this->serperSearch(
            $searchString,
            $limit,
            $data['language'] ?? null
        );

        // 3) extract & dedupe hosts
        $domains = collect($urls)
            ->map(fn($u) => parse_url($u, PHP_URL_HOST))
            ->filter()
            ->map(fn($h) => Str::lower($h))
            ->unique();

        // 4) drop gov / edu / org if requested
        if (! empty($data['exclude_gov_edu'])) {
            $domains = $domains->reject(fn($h) =>
            preg_match('/\.(gov|edu|org)$/i', $h)
            );
        }

        // 5) drop any you already have in DB
        $existing = Website::whereIn('domain_name', $domains)
            ->pluck('domain_name')
            ->map(fn($d) => Str::lower($d));
        $fresh = $domains->diff($existing);

        // 6) blacklist social & retail giants
        $blacklist = ['amazon','facebook','instagram','tiktok','twitter','linkedin','youtube'];
        $fresh = $fresh->reject(fn($h) =>
        collect($blacklist)->contains(fn($b) =>
            // match label as either subdomain or SLD
        preg_match('/(^|\.)' . preg_quote($b, '/') . '(\.|$)/i', $h)
        )
        );

        // 7) take first 100 and return
        $final = $fresh
            ->values()
            ->take($limit)
            ->all();

        return response()->json([
            'total'      => $domains->count(),
            'duplicates' => $domains->count() - count($final),
            'new'        => $final,
        ]);
    }



    /* ----------  GET /tools/discover/export  ---------- */
    public function exportCsv(Request $request)
    {
        $csv = implode("\n", $request->query('domains', []));
        return Response::make($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=domains.csv',
        ]);
    }

    /* ----------  private helper: Serper.dev  ---------- */
    /**
     * Fetch up to $total result URLs from Serper.dev, paging by up to 100 at a time.
     *
     * @param  string      $q        The full SERP query
     * @param  int         $total    How many URLs you want at most
     * @param  string|null $isoLang  Two-letter ISO code, or null
     * @return string[]             Array of unique result URLs
     */
    private function serperSearch(string $q, int $total, ?string $isoLang): array
    {
        $results  = [];
        // Serper.dev supports up to 100 organic results per request
        $pageSize = min($total, 100);
        $start    = 0;

        while (count($results) < $total) {
            $payload = [
                'q'     => $q,
                'num'   => $pageSize,      // ask for up to 100 links
                'start' => $start,         // offset into the SERP
                'gl'    => $isoLang ?? 'us',
            ];

            $res = Http::withHeaders([
                'X-API-KEY'    => config('services.serper.key'),
                'Content-Type' => 'application/json',
            ])->post('https://google.serper.dev/search', $payload);

            if (!$res->successful()) {
                Log::warning('Serper error', [
                    'status' => $res->status(),
                    'body'   => $res->body(),
                ]);
                break;
            }

            $batch = collect($res->json('organic', []))
                ->pluck('link')
                ->toArray();

            // Merge new batch, dedupe
            $results = array_unique(array_merge($results, $batch));

            // If fewer than requested came back, we've hit the last page
            if (count($batch) < $pageSize) {
                break;
            }

            // Advance offset for the next page
            $start += $pageSize;
        }

        // Trim to exactly $total entries
        return array_slice($results, 0, $total);
    }

}
