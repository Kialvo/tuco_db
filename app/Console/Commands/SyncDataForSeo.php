<?php

namespace App\Console\Commands;

use App\Models\Website;
use App\Services\DataForSeoService;
use Illuminate\Console\Command;

class SyncDataForSeo extends Command
{
    protected $signature   = 'dataforseo:sync';
    protected $description = 'Sync DataforSEO metrics for all non-deleted websites.';

    // TESTING: set to 200. Remove ->take() line below to sync all.
    private const TEST_LIMIT = 200;

    public function handle(DataForSeoService $service): int
    {
        $websites = Website::query()
            ->select(['id', 'domain_name'])
            ->get();

        $total = $websites->count();
        $this->info("Starting DataforSEO sync for {$total} domains...");

        $domains = $websites->pluck('domain_name', 'id')->all(); // [id => domain]

        // All data fetched in bulk — just a few API calls total
        $this->info('Fetching data from DataforSEO...');
        $results = $service->fetchBatch(array_values($domains));
        $this->info('Data received. Writing to database...');

        // Build upsert payload — batch DB writes instead of one query per row
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

        // Write in chunks of 500 — one query per chunk instead of one per row
        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach (array_chunk($rows, 500) as $chunk) {
            Website::upsert($chunk, ['id'], ['ms', 'organic_keywords', 'organic_traffic', 'kw_traffic_ratio']);
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();
        $this->info("DataforSEO sync complete. {$updated}/{$total} domains updated.");

        return Command::SUCCESS;
    }
}
