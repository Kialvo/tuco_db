<?php
// app/Http/Controllers/WebsiteImportController.php

namespace App\Http\Controllers;

use App\Http\Requests\WebsiteMetricsImportRequest;
use App\Imports\WebsiteMetricsCsvImporter;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WebsiteImportController extends Controller
{
    public function index()
    {
        // dedicated page, like New Entries import page
        return view('websites.import');
    }

    // app/Http/Controllers/WebsiteImportController.php
    public function sample()
    {
        // Exact headers required (no parentheses, header-only, no data rows)
        $headers = [
            'Domain',
            'DR',
            'TF',
            'CF',
            'Ahrefs Keywords',
            'Ahrefs Traffic',
        ];

        $fh = fopen('php://temp', 'r+');

        // Optional: add UTF-8 BOM if you open in Excel a lot
        // fwrite($fh, "\xEF\xBB\xBF");

        fputcsv($fh, $headers);
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="websites-import-sample.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }


    public function preview(WebsiteMetricsImportRequest $request)
    {
        $csv = $this->getCsv($request);
        if ($csv === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Please upload a CSV or provide a Google Sheet URL/ID.',
            ], 422);
        }

        $importer = new WebsiteMetricsCsvImporter(
            decimalComma: (bool)$request->boolean('decimal_comma', false)
        );

        $parsed = $importer->parse(
            csv: $csv,
            hasHeader: (bool)$request->boolean('has_header', true),
            previewLimit: 1000
        );

        // Load current metrics for diff
        $domains = collect($parsed['rows'])->pluck('norm.domain_name')->filter()->unique()->values()->all();

        $current = Website::query()
            ->whereIn('domain_name', $domains)
            ->get(['domain_name','DR','TF','CF','ahrefs_keyword','ahrefs_traffic'])
            ->keyBy(fn($w) => strtolower($w->domain_name));

        $preview = [];
        $errorsCount = 0;

        foreach ($parsed['rows'] as $r) {
            $n   = $r['norm'];
            $key = strtolower($n['domain_name'] ?? '');
            $cur = $current->get($key);

            $row = [
                'line' => $r['line'],
                'data' => [
                    'domain_name'    => $n['domain_name'] ?? null,

                    // Current values:
                    'DR_current'             => $cur->DR ?? null,
                    'TF_current'             => $cur->TF ?? null,
                    'CF_current'             => $cur->CF ?? null,
                    'ahrefs_keyword_current' => $cur->ahrefs_keyword ?? null,
                    'ahrefs_traffic_current' => $cur->ahrefs_traffic ?? null,

                    // New values (if column present & non-empty):
                    'DR'             => array_key_exists('DR', $n) ? $n['DR'] : '__UNCHANGED__',
                    'TF'             => array_key_exists('TF', $n) ? $n['TF'] : '__UNCHANGED__',
                    'CF'             => array_key_exists('CF', $n) ? $n['CF'] : '__UNCHANGED__',
                    'ahrefs_keyword' => array_key_exists('ahrefs_keyword', $n) ? $n['ahrefs_keyword'] : '__UNCHANGED__',
                    'ahrefs_traffic' => array_key_exists('ahrefs_traffic', $n) ? $n['ahrefs_traffic'] : '__UNCHANGED__',
                ],
                'errors' => $r['errors'],
                'valid'  => $r['valid'],
            ];

            if (!$r['valid'] && !empty($r['errors'])) $errorsCount++;

            $preview[] = $row;
        }

        $token = 'websites_metrics_import_' . Str::uuid()->toString();
        Cache::put($token, $parsed['rows'], now()->addHours(2));

        return response()->json([
            'ok'     => true,
            'token'  => $token,
            'limit'  => $parsed['limit'],
            'stats'  => [
                'total'      => count($parsed['rows']),
                'will_update'=> collect($parsed['rows'])->where('valid', true)->count(),
                'invalid'    => collect($parsed['rows'])->where('valid', false)->count(),
            ],
            'errors_count' => $errorsCount,
            'preview'      => $preview,
        ]);
    }

    // app/Http/Controllers/WebsiteImportController.php

    public function commit(Request $request)
    {
        $request->validate(['token' => ['required','string']]);

        // IMPORTANT: coerce to a plain string, don't pass a Stringable to Cache
        $token = (string) $request->input('token');

        $rows = Cache::pull($token);
        if (!$rows || !is_array($rows)) {
            return response()->json([
                'ok'      => false,
                'message' => 'Import session expired. Please run Preview again.',
            ], 410);
        }

        $importer = new \App\Imports\WebsiteMetricsCsvImporter();
        $result   = $importer->commit($rows, 1000);

        return response()->json([
            'ok'      => true,
            'created' => 0,
            'updated' => $result['updated'],
            'failed'  => $result['failed'],
        ]);
    }


    /* ---------------- helpers ---------------- */

    private function getCsv(WebsiteMetricsImportRequest $request): ?string
    {
        if ($request->file('file')) {
            return file_get_contents($request->file('file')->getRealPath());
        }

        $sheet = trim((string)$request->input('sheet_url', ''));
        if ($sheet === '') return null;

        // full Google Sheets URL or raw ID
        $id = $sheet;
        if (str_contains($sheet, 'docs.google.com')) {
            if (preg_match('#/spreadsheets/d/([^/]+)/#', $sheet, $m)) {
                $id = $m[1];
            }
        }
        $csvUrl = "https://docs.google.com/spreadsheets/d/{$id}/gviz/tq?tqx=out:csv";

        try {
            return @file_get_contents($csvUrl) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}
