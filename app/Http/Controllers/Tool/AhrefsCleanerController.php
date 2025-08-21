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

        // read rows
        while (($row = fgetcsv($handle, 0, $delim)) !== false) {
            $host = $this->extractHostFromRow($headers, $row);
            if (!$host) continue;

            // normalize host (lowercase, strip www.)
            $host = Str::lower($host);
            $host = preg_replace('/^www\./i', '', $host);

            $hosts[] = $host;
        }
        fclose($handle);

        // unique
        $hosts = array_values(array_unique(array_filter($hosts)));

        // ---- rule (1): drop .gov/.edu/.org (including multi-label like .gov.ro) ----
        $hosts = array_values(array_filter($hosts, function ($h) {
            return !preg_match('/\.(gov|edu|org)(\.|$)/i', $h);
        }));

        // ---- rule (2): drop "big websites" list (exact domains + their subdomains) ----
        $big = [
            'facebook.com','twitter.com','instagram.com','linkedin.com','youtube.com','pinterest.com',
            'reddit.com','tiktok.com','whatsapp.com','amazon.com','ebay.com','aliexpress.com',
            'netflix.com','booking.com','airbnb.com','tripadvisor.com','apple.com','microsoft.com',
            'google.com','yahoo.com','bing.com','paypal.com','cnn.com','nytimes.com','forbes.com',
            'bbc.com','bloomberg.com','nike.com','adidas.com','zalando.com','shein.com','hm.com',
            'puma.com','gucci.com','prada.com','louisvuitton.com','samsung.com','huawei.com',
            'sony.com','lenovo.com','hp.com','dell.com','lg.com',
            'pokerstars.ro','forbes.ro','volkswagen.ro','bmw.ro',
            'news.microsoft.com','news.samsung.com',
        ];

        $hosts = array_values(array_filter($hosts, function ($h) use ($big) {
            foreach ($big as $d) {
                if ($h === $d || Str::endsWith($h, '.'.$d)) {
                    return false;
                }
            }
            // families with many ccTLDs
            if (preg_match('/(^|\.)amazon\.[a-z.]+$/i', $h)) return false;
            if (preg_match('/(^|\.)ebay\.[a-z.]+$/i', $h))   return false;
            return true;
        }));

        // ---- rule (3): drop already in our database (websites table) ----
        $dbExisting = Website::whereIn('domain_name', $hosts)
            ->pluck('domain_name')
            ->map(fn($d) => Str::lower($d))
            ->all();

        if (!empty($dbExisting)) {
            $existingSet = array_flip($dbExisting);
            $hosts = array_values(array_filter($hosts, fn($h) => !isset($existingSet[$h])));
        }

        // ---- rule (4): drop those in "new entry" with status & first contact < 15 days ----
        // This block only runs if you have a table named `new_entries` with columns:
        // domain_name, status, first_contact_date (DATE/DATETIME)
        if (Schema::hasTable('new_entries')) {
            $cutoff = now()->subDays(15);
            $blocked = DB::table('new_entries')
                ->select('domain_name')
                ->whereIn('domain_name', $hosts)
                ->where('status', 'Waiting for 1st Answer')
                ->where('first_contact_date', '>=', $cutoff)
                ->pluck('domain_name')
                ->map(fn($d) => Str::lower($d))
                ->all();

            if (!empty($blocked)) {
                $blockedSet = array_flip($blocked);
                $hosts = array_values(array_filter($hosts, fn($h) => !isset($blockedSet[$h])));
            }
        }

        // ---- export cleaned CSV (one column: domain) ----
        $out = "domain\n" . implode("\n", $hosts);
        $filename = 'cleaned_ahrefs_' . now()->format('Ymd_His') . '.csv';

        return Response::make($out, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
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
