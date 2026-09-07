<?php

namespace App\Http\Controllers;

use App\Http\Requests\WebsiteImportRequest;
use App\Imports\WebsiteCsvImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Bulk-create Domains from a CSV.
 *
 * Replaces the metrics-only import that lived at this route until Sep 2026 —
 * it could refresh DR/TF/CF/Ahrefs on existing domains but never create one.
 *
 * Preview first, always: imported domains go live as `active` and are visible
 * to customers immediately, so nothing is written until the admin has seen the
 * calculated prices, the rejected rows and the list of domains that already
 * exist.
 */
class WebsiteImportController extends Controller
{
    /** How long a previewed file waits for its confirmation. */
    private const TOKEN_TTL_MINUTES = 60;

    public function index()
    {
        return view('websites.import', [
            'templateHeaders' => WebsiteCsvImporter::TEMPLATE_HEADERS,
            'allowedTypes' => WebsiteCsvImporter::ALLOWED_TYPES,
            'allowedLinkBuilders' => WebsiteCsvImporter::ALLOWED_LINK_BUILDERS,
            'previewLimit' => WebsiteCsvImporter::PREVIEW_LIMIT,
        ]);
    }

    /**
     * Header-only template.
     *
     * Deliberately no example row: imported domains go straight to customers,
     * and a forgotten "example.com" would be live.
     */
    public function sample()
    {
        $fh = fopen('php://temp', 'r+');

        // BOM so Excel opens the euro sign in "Link Builder €" correctly.
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, WebsiteCsvImporter::TEMPLATE_HEADERS);
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="domains-import-template.csv"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function preview(WebsiteImportRequest $request)
    {
        $csv = file_get_contents($request->file('file')->getRealPath());
        if ($csv === false || trim($csv) === '') {
            return response()->json([
                'ok' => false,
                'message' => 'That file appears to be empty.',
            ], 422);
        }

        $parsed = (new WebsiteCsvImporter)->parse(
            csv: $csv,
            hasHeader: (bool) $request->boolean('has_header', true)
        );

        // The downloadable template is header-only, so this is the first thing
        // anyone hits: without an explicit message they get an empty screen and
        // a disabled Import button with no explanation.
        if ($parsed['rows'] === []) {
            return response()->json([
                'ok' => false,
                'message' => 'This file has no domains in it — only the header row. '
                    .'Add one line per domain underneath the headers and upload it again.',
            ], 422);
        }

        $token = 'websites_import_'.Str::uuid()->toString();
        Cache::put($token, $parsed['rows'], now()->addMinutes(self::TOKEN_TTL_MINUTES));

        return response()->json([
            'ok' => true,
            'token' => $token,
            'stats' => $parsed['stats'],
            'truncated' => $parsed['truncated'],
            'preview_limit' => WebsiteCsvImporter::PREVIEW_LIMIT,
            'rows' => array_map($this->presentRow(...), $parsed['rows']),
        ]);
    }

    public function commit(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'default_existing_action' => ['nullable', 'in:update,skip'],
            'existing_actions' => ['nullable', 'array'],
            'existing_actions.*' => ['in:update,skip'],
        ]);

        $rows = Cache::pull((string) $validated['token']);
        if (! $rows || ! is_array($rows)) {
            return response()->json([
                'ok' => false,
                'message' => 'This import session has expired. Please upload the file and preview it again.',
            ], 410);
        }

        $result = (new WebsiteCsvImporter)->commit(
            rows: $rows,
            existingActions: $validated['existing_actions'] ?? [],
            defaultExistingAction: $validated['default_existing_action'] ?? 'skip'
        );

        return response()->json(['ok' => true] + $result);
    }

    /**
     * Flatten a parsed row into what the preview table shows.
     *
     * The calculated Price and Sensitive Topic Price are included on purpose —
     * seeing them before committing is the whole point of the preview.
     */
    private function presentRow(array $row): array
    {
        $d = $row['data'];

        return [
            'line' => $row['line'],
            'domain_name' => $d['domain_name'],
            'currency_code' => $d['currency_code'] ?? null,
            'publisher_price' => $d['publisher_price'] ?? null,
            'special_topic_price' => $d['special_topic_price'] ?? null,
            'link_builder_amount' => $d['link_builder_amount'] ?? null,
            'price' => $d['price'] ?? null,
            'sensitive_topic_price' => $d['sensitive_topic_price'] ?? null,
            'betting' => $d['betting'] ?? null,
            'trading' => $d['trading'] ?? null,
            'categories' => count($row['category_ids'] ?? []),
            'exists' => (bool) ($row['exists'] ?? false),
            'existing_trashed' => (bool) ($row['existing_trashed'] ?? false),
            'valid' => (bool) $row['valid'],
            'errors' => $row['errors'],
        ];
    }
}
