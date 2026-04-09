<?php

namespace App\Console\Commands;

use App\Models\NewEntry;
use App\Models\Website;
use App\Services\DataForSeoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncDataForSeo extends Command
{
    protected $signature   = 'dataforseo:sync';
    protected $description = 'Sync DataforSEO metrics for all websites and new entries.';

    public function handle(DataForSeoService $service): int
    {
        // ── Websites ─────────────────────────────────────────────────────────
        $websites = Website::query()->select(['id', 'domain_name'])->get();
        $total    = $websites->count();
        $this->info("Starting DataforSEO sync for {$total} website domains...");

        $this->info('Fetching data from DataforSEO...');
        $results = $service->fetchBatch($websites->pluck('domain_name')->all());
        $this->info('Data received. Writing to database...');

        $rows    = [];
        $updated = 0;
        foreach ($websites as $website) {
            $data = $results[$website->domain_name] ?? null;
            if (! $data) continue;
            $rows[] = [
                'id'               => $website->id,
                'ms'               => $data['ms'],
                'organic_keywords' => $data['organic_keywords'],
                'organic_traffic'  => $data['organic_traffic'],
                'kw_traffic_ratio' => $data['kw_traffic_ratio'],
            ];
            $updated++;
        }

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach (array_chunk($rows, 500) as $chunk) {
            $this->bulkUpdate('websites', $chunk);
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();
        $this->info("Websites sync complete. {$updated}/{$total} domains updated.");

        // ── New Entries ──────────────────────────────────────────────────────
        $entries = NewEntry::query()->select(['id', 'domain_name'])->get();
        $total   = $entries->count();
        $this->info("Starting DataforSEO sync for {$total} new entry domains...");

        $this->info('Fetching data from DataforSEO...');
        $results = $service->fetchBatch($entries->pluck('domain_name')->all());
        $this->info('Data received. Writing to database...');

        $rows    = [];
        $updated = 0;
        foreach ($entries as $entry) {
            $data = $results[$entry->domain_name] ?? null;
            if (! $data) continue;
            $rows[] = [
                'id'               => $entry->id,
                'ms'               => $data['ms'],
                'organic_keywords' => $data['organic_keywords'],
                'organic_traffic'  => $data['organic_traffic'],
                'kw_traffic_ratio' => $data['kw_traffic_ratio'],
            ];
            $updated++;
        }

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach (array_chunk($rows, 500) as $chunk) {
            $this->bulkUpdate('new_entries', $chunk);
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();
        $this->info("New entries sync complete. {$updated}/{$total} domains updated.");

        return Command::SUCCESS;
    }

    /**
     * Bulk-update ms/organic_keywords/organic_traffic/kw_traffic_ratio for a
     * set of rows using a single UPDATE … CASE/WHEN query per chunk.
     * Avoids INSERT entirely, so NOT NULL constraints on other columns are irrelevant.
     */
    private function bulkUpdate(string $table, array $rows): void
    {
        if (empty($rows)) return;

        $cols   = ['ms', 'organic_keywords', 'organic_traffic', 'kw_traffic_ratio'];
        $idList = implode(',', array_map(fn($r) => (int) $r['id'], $rows));

        $setClauses = [];
        foreach ($cols as $col) {
            $cases = implode(' ', array_map(
                fn($r) => 'WHEN ' . (int)$r['id'] . ' THEN ' .
                    (is_null($r[$col]) ? 'NULL' : (float) $r[$col]),
                $rows
            ));
            $setClauses[] = "`$col` = CASE `id` $cases END";
        }
        $setClauses[] = '`updated_at` = NOW()';

        DB::statement(
            'UPDATE `' . $table . '` SET ' . implode(', ', $setClauses) .
            ' WHERE `id` IN (' . $idList . ')'
        );
    }
}
