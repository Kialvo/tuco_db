<?php
// app/Imports/WebsiteMetricsCsvImporter.php

namespace App\Imports;

use App\Models\Website;

class WebsiteMetricsCsvImporter
{
    /** Normalize CSV headers (case/space-insensitive) → internal keys */
    private const HEADER_MAP = [
        'domain'            => 'domain_name',
        'website'           => 'domain_name',
        'site'              => 'domain_name',
        'domain_name'       => 'domain_name',

        'dr'                => 'DR',
        'tf'                => 'TF',
        'cf'                => 'CF',
        'ahrefs keywords'   => 'ahrefs_keyword',
        'ahrefs_keywords'   => 'ahrefs_keyword',
        'ahrefs traffic'    => 'ahrefs_traffic',
        'ahrefs_traffic'    => 'ahrefs_traffic',
    ];

    /** Only these columns are updated */
    private const METRIC_COLS = ['DR','TF','CF','ahrefs_keyword','ahrefs_traffic'];

    private bool $decimalComma = false;
    private array $presentCols = [];

    public function __construct(bool $decimalComma = false)
    {
        $this->decimalComma = $decimalComma;
    }

    public function parse(string $csv, bool $hasHeader = true, int $previewLimit = 1000): array
    {
        $rows   = [];
        $issues = [];
        $fh = fopen('php://memory', 'r+');
        fwrite($fh, $csv);
        rewind($fh);

        $header = null;
        $lineNo = 0;

        while (($cols = fgetcsv($fh)) !== false) {
            $lineNo++;

            if ($lineNo === 1 && $hasHeader) {
                $header = $this->normalizeHeader($cols);
                $this->presentCols = $this->detectPresentMetricCols($header);
                continue;
            }

            $raw = $this->assocRow($cols, $header);
            if (!$raw) {
                $issues[] = ['line' => $lineNo, 'error' => 'Empty row'];
                continue;
            }

            $norm = $this->normalizeRow($raw);
            [$ok, $err] = $this->validateRow($norm);
            $op = $this->resolveOperation($norm); // update or skip
            $isValidUpdate = $ok && ($op === 'update');

            $rows[] = [
                'line'   => $lineNo,
                'raw'    => $raw,
                'norm'   => $norm + ['__op' => $op],
                'valid'  => $isValidUpdate,
                'errors' => $isValidUpdate ? [] : ($ok ? ['Domain not found in Websites.'] : $err),
            ];

            if (count($rows) >= $previewLimit) {
                $issues[] = ['line' => $lineNo, 'error' => "Preview limited to first {$previewLimit} rows."];
                break;
            }
        }

        fclose($fh);
        return [
            'rows'   => $rows,
            'issues' => $issues,
            'header' => $header,
            'limit'  => min($previewLimit, count($rows)),
        ];
    }

    public function commit(array $rows, int $chunk = 1000): array
    {
        $updated = 0;
        $failed  = 0;

        foreach (array_chunk($rows, $chunk) as $pack) {
            $domains = array_values(array_unique(array_map(fn($r) => $r['norm']['domain_name'] ?? null, $pack)));
            $domains = array_filter($domains);

            $existing = Website::query()
                ->whereIn('domain_name', $domains)
                ->get(['id','domain_name'])
                ->keyBy(fn($w) => strtolower($w->domain_name));

            foreach ($pack as $r) {
                if (!$r['valid']) { $failed++; continue; }
                $n = $r['norm'];
                $key = strtolower($n['domain_name']);
                $website = $existing->get($key);
                if (!$website) { $failed++; continue; }

                $payload = [];
                foreach (self::METRIC_COLS as $m) {
                    // Update only if column present AND non-empty in the CSV row
                    if (array_key_exists($m, $n) && $n[$m] !== null) {
                        $payload[$m] = $n[$m];
                    }
                }

                if (!$payload) { continue; } // nothing to change in this row

                $website->fill($payload)->save();
                $updated++;
            }
        }

        return compact('updated','failed');
    }

    /* ---------------- helpers ---------------- */

    private function normalizeHeader(array $cols): array
    {
        return array_map(fn($h) => $this->norm($h), $cols);
    }

    private function detectPresentMetricCols(array $header): array
    {
        $present = [];
        foreach ($header as $h) {
            $key = self::HEADER_MAP[$h] ?? null;
            if ($key && in_array($key, self::METRIC_COLS, true)) {
                $present[$key] = true;
            }
        }
        return $present;
    }

    private function assocRow(array $cols, ?array $header): ?array
    {
        if ($header) {
            $row = [];
            foreach ($cols as $i => $val) {
                $row[$header[$i] ?? "col_$i"] = $val;
            }
            return $row;
        }
        $row = [];
        foreach ($cols as $i => $val) {
            $row["col_$i"] = $val;
        }
        return $row;
    }

    private function normalizeRow(array $raw): array
    {
        $out = [];

        foreach ($raw as $k => $v) {
            $map = self::HEADER_MAP[$this->norm($k)] ?? null;
            if (!$map) continue;

            $val = is_string($v) ? trim($v) : $v;

            switch ($map) {
                case 'domain_name':
                    $out['domain_name'] = $this->normalizeDomain($val);
                    break;

                case 'DR':
                case 'TF':
                case 'CF':
                case 'ahrefs_keyword':
                case 'ahrefs_traffic':
                    $out[$map] = $this->toIntOrNull($val);
                    break;
            }
        }

        $out['__present'] = $this->presentCols;
        return $out;
    }

    private function validateRow(array $n): array
    {
        $err = [];
        if (empty($n['domain_name'])) {
            $err[] = 'Missing Domain.';
        }

        $hasAnyMetricKey = (bool) array_intersect(array_keys($n), self::METRIC_COLS);
        if (!$hasAnyMetricKey) {
            $err[] = 'No metric columns provided (DR / TF / CF / Ahrefs Keywords / Ahrefs Traffic).';
        }

        return [empty($err), $err];
    }

    private function resolveOperation(array $n): string
    {
        if (empty($n['domain_name'])) return 'skip';
        return Website::query()->where('domain_name', $n['domain_name'])->exists() ? 'update' : 'skip';
    }

    private function norm(?string $s): string
    {
        $s = (string)$s;
        $s = trim(mb_strtolower($s));
        $s = preg_replace('/\s+/', ' ', $s);
        return $s;
    }

    private function normalizeDomain(?string $s): ?string
    {
        if (!$s) return null;
        $s = trim($s);
        $s = preg_replace('#^https?://#i', '', $s);
        $s = preg_replace('#^www\.#i', '', $s);
        $s = explode('/', $s)[0];
        return strtolower($s) ?: null;
    }

    private function toIntOrNull($val): ?int
    {
        if ($val === null || $val === '') return null;
        $s = preg_replace('/[^\d\-]/', '', (string)$val);
        if ($s === '' || $s === '-') return null;
        return (int)$s;
    }
}
