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
            // either query or tld must be present
            'query'           => 'nullable|string|max:255|required_without:tld',
            'tld'             => 'nullable|string|max:10|required_without:query',
            'language'        => 'nullable|alpha|size:2',
            'exclude_gov_edu' => 'sometimes|boolean',
            // <-- no more 'limit' here
        ]);

        // ← hard-code 100
        $limit = 100;

        // build the SERP query pieces
        $pieces = [];

        if (!empty($data['query'])) {
            $pieces[] = $data['query'];
        }

        if (!empty($data['tld'])) {
            // normalize “.IT” → “it”
            $dotless = ltrim(Str::lower($data['tld']), '.');
            $pieces[] = 'site:.' . $dotless;
        }

        $searchString = implode(' ', $pieces);

        // fetch full URLs (up to $limit) from Serper.dev
        $urls = $this->serperSearch(
            $searchString,
            $limit,
            $data['language'] ?? null
        );

        // drop out any existing hosts, gov/edu/org, etc.
        $domains = collect($urls)
            ->map(fn($u) => parse_url($u, PHP_URL_HOST))
            ->filter()
            ->map(fn($h) => Str::lower($h))
            ->unique();

        if (!empty($data['exclude_gov_edu'])) {
            $domains = $domains->reject(fn($h) =>
            Str::endsWith($h, ['.gov', '.edu', '.org'])
            );
        }

        $existing = Website::whereIn('domain_name', $domains)
            ->pluck('domain_name')
            ->map(fn($d) => Str::lower($d));

        $freshHosts = $domains->diff($existing);

        // pick the main domains (just hostnames) up to $limit
        $mainDomains = $freshHosts
            ->values()         // reindex numeric keys
            ->take($limit)     // enforce our hard-coded 100
            ->all();

        return response()->json([
            'total'      => $domains->count(),
            'duplicates' => $domains->count() - count($mainDomains),
            'new'        => $mainDomains,  // ["example.com", "foo.org", …]
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
