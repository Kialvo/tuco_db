<?php

namespace App\Http\Controllers\Tool;

use App\Http\Controllers\Controller;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AhrefsCleanerController extends Controller
{
    public function index()
    {
        return view('tools.ahrefs_cleaner');
    }

    public function run(Request $request)
    {
        $data = $request->validate([
            'csv' => 'required|file|mimes:csv,txt|max:20480', // up to ~20MB
        ]);

        $file = $data['csv'];

        // ---- read CSV (auto-detect , or ;) ----
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            abort(400, 'Cannot open uploaded file.');
        }

        // detect delimiter
        $firstLine = fgets($handle) ?: '';
        $delim = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        rewind($handle);

        // read header
        $headers = fgetcsv($handle, 0, $delim) ?: [];
        $headers = array_map(fn($h) => Str::lower(trim((string)$h)), $headers);

        $hosts = [];
        while (($row = fgetcsv($handle, 0, $delim)) !== false) {
            $host = $this->extractHostFromRow($headers, $row);
            if (!$host) continue;
            $host = Str::lower($host);
            $host = preg_replace('/^www\./i', '', $host);
            $hosts[] = $host;
        }
        fclose($handle);

        // unique base list
        $hosts = array_values(array_unique(array_filter($hosts)));

        // We'll track reasons per removed domain
        $reasons = []; // [domain => ['reason1','reason2', ...]]

        // helper to mark removed
        $markRemoved = function(array $toRemove, string $reason) use (&$reasons) {
            foreach ($toRemove as $d) {
                $reasons[$d] = array_values(array_unique(array_merge($reasons[$d] ?? [], [$reason])));
            }
        };

        // ---- rule (1): .gov/.edu/.org (incl. multi-label like .gov.ro)
        $drop1 = array_values(array_filter($hosts, fn($h) => preg_match('/\.(gov|edu|org)(\.|$)/i', $h)));
        $markRemoved($drop1, '.gov/.edu/.org');
        $hosts = array_values(array_diff($hosts, $drop1));

        // ---- rule (2): big platforms (exact + subdomains, and families like amazon.xx)
        $drop2 = array_values(array_filter($hosts, fn($h) => $this->isBigPlatform($h)));
        $markRemoved($drop2, 'big platform');
        $hosts = array_values(array_diff($hosts, $drop2));

        // ---- rule (3): already in our DB (websites table)
        $dbExisting = Website::whereIn('domain_name', $hosts)
            ->pluck('domain_name')
            ->map(fn($d) => Str::lower($d))
            ->all();
        $drop3 = array_values(array_intersect($hosts, $dbExisting));
        $markRemoved($drop3, 'already in websites');
        $hosts = array_values(array_diff($hosts, $drop3));

        // ---- rule (4): "new_entries" status + first contact < 15 days
        if (Schema::hasTable('new_entries')) {
            $cutoff = now()->subDays(15);
            $blocked = DB::table('new_entries')
                ->select('domain_name')
                ->whereIn('domain_name', $hosts)
                ->where('status', 'waiting_for_first_answer')
                ->where('first_contact_date', '>=', $cutoff)
                ->pluck('domain_name')
                ->map(fn($d) => Str::lower($d))
                ->all();

            $drop4 = array_values(array_intersect($hosts, $blocked));
            $markRemoved($drop4, 'new entry (<15d) & waiting');
            $hosts = array_values(array_diff($hosts, $drop4));
        }

        // remaining hosts are "kept"
        $kept = $hosts;

        // build removed list for the table [{domain:'x', reason:[...]}]
        $removed = [];
        foreach ($reasons as $domain => $why) {
            $removed[] = ['domain' => $domain, 'reason' => $why];
        }
        // optional: sort alphabetically
        usort($removed, fn($a, $b) => strcmp($a['domain'], $b['domain']));
        sort($kept, SORT_STRING);

        // create a data URL for the Download button
        $csvOut = "domain\n" . implode("\n", $kept);
        $downloadDataUrl = 'data:text/csv;base64,' . base64_encode($csvOut);

        return view('tools.ahrefs_cleaner', [
            'removed'           => $removed,
            'kept_count'        => count($kept),
            'removed_count'     => count($removed),
            'total_count'       => count($kept) + count($removed),
            'download_data_url' => $downloadDataUrl,
        ]);
    }
    private function isBigPlatform(string $host): bool
    {
        // Exact-domain list (and their subdomains)
        static $big = [
            'facebook.com','twitter.com','instagram.com','linkedin.com','youtube.com','pinterest.com',
            'reddit.com','tiktok.com','whatsapp.com','amazon.com','ebay.com','aliexpress.com',
            'netflix.com','booking.com','airbnb.com','tripadvisor.com','apple.com','microsoft.com',
            'google.com','yahoo.com','bing.com','paypal.com','cnn.com','nytimes.com','forbes.com',
            'bbc.com','bloomberg.com','nike.com','adidas.com','zalando.com','shein.com','hm.com',
            'puma.com','gucci.com','prada.com','louisvuitton.com','samsung.com','huawei.com',
            'sony.com','lenovo.com','hp.com','dell.com','lg.com',
            'pokerstars.ro','forbes.ro','volkswagen.ro','bmw.ro',
            'news.microsoft.com','news.samsung.com',

            // Extended blacklist you provided earlier:
            'x.com','imdb.com','twitchtracker.com','audacy.com','threads.com','spotify.com','deezer.com',
            'sedo.com','podfollow.com','ebay.com','play.google.com','apps.apple.com','reddit.com',
            'chromewebstore.google.com',
            // podcasts & open.spotify subdomains etc will be caught by subdomain check below
            'podcasts.apple.com','open.spotify.com','chromewebstore.google.com','play.google.com','apps.apple.com',
        ];

        // If $host equals or ends with any of the domains above -> block
        foreach ($big as $d) {
            if ($host === $d || Str::endsWith($host, '.'.$d)) {
                return true;
            }
        }

        // Families with many ccTLDs (amazon.xx / ebay.xx)
        if (preg_match('/(^|\.)amazon\.[a-z.]+$/i', $host)) return true;
        if (preg_match('/(^|\.)ebay\.[a-z.]+$/i', $host))   return true;

        // Major social and app store families across subdomains (safety belt)
        $families = [
            'facebook', 'instagram', 'twitter', 'x', 'tiktok', 'linkedin',
            'youtube', 'spotify', 'deezer', 'threads', 'whatsapp', 'reddit',
            'google', 'apple'
        ];
        foreach ($families as $needle) {
            if (preg_match('/(^|\.)'.$needle.'\.[a-z0-9.-]+$/i', $host)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Find a usable host from the row.
     * Tries common Ahrefs columns first; otherwise scans any field for URL/domain.
     */
    private function extractHostFromRow(array $headers, array $row): ?string
    {
        $candidates = [
            'domain','domain name','referring domain','ref domain',
            'target','target url','url','referring page',
        ];

        // use known columns first
        foreach ($candidates as $key) {
            $i = array_search($key, $headers, true);
            if ($i !== false && isset($row[$i])) {
                $h = $this->valueToHost($row[$i]);
                if ($h) return $h;
            }
        }

        // else scan all fields to locate first URL/domain-ish
        foreach ($row as $val) {
            $h = $this->valueToHost($val);
            if ($h) return $h;
        }
        return null;
    }

    private function valueToHost($val): ?string
    {
        $val = trim((string)$val);
        if ($val === '') return null;

        // If it's a URL, parse host
        if (preg_match('#^https?://#i', $val)) {
            $host = parse_url($val, PHP_URL_HOST);
            return $host ?: null;
        }

        // If it's domain-like (foo.bar or foo.bar.baz)
        if (preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $val)) {
            return $val;
        }

        return null;
    }
}
