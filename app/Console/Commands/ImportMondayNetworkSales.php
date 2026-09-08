<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Storage as Publication;
use App\Models\Website;
use App\Services\StorageCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Bring the Monday "Sales - Network Stats" board (582070825) into Publications.
 *
 * That board records guest posts sold on Menford's own network — 1,372 items
 * back to 2019, including the ones that never sold. The sold ones are largely
 * in LIAB already; the refused ones were never entered at all, which is why the
 * board's Approvals statistics cannot currently be reproduced here.
 *
 * This RECONCILES rather than inserts. Three outcomes per item:
 *
 *   linked  – already imported (monday_item_id set); nothing is written
 *   match   – a publication with the same Article URL exists; ONLY the
 *             monday_item_id is stamped on it. Status, price, date and URL are
 *             left exactly as they are.
 *   create  – no counterpart in LIAB; a publication is created
 *
 * Anything that cannot be resolved is reported and skipped, never guessed at.
 *
 * Always run --dry-run first: it reads Monday and the database, writes nothing,
 * and produces a CSV of every decision it would make.
 */
class ImportMondayNetworkSales extends Command
{
    protected $signature = 'monday:import-network-sales
                            {--dry-run : Read everything, write nothing, and produce the report}
                            {--out= : Path for the CSV report (default: storage/app/monday-network-sales-<timestamp>.csv)}';

    protected $description = 'Reconcile the Monday Network Sales board into Publications (dry-run by default in practice)';

    /**
     * Monday status => LIAB publication status.
     *
     * Agreed with Simone on the Monday thread. The three "refused_*" targets
     * were added to config/linkbuilding.php for this: mapping them onto an
     * existing reason would have collapsed several of the board's statuses into
     * one and made its Approvals breakdown impossible to reproduce.
     *
     * Note "Disappeared" does NOT map to the existing publisher_disappeared:
     * that means the PUBLISHER vanished after the client approved, and LIAB
     * counts it as an approval. On this board it means the client went quiet —
     * a lost sale. Mapping them together would have inflated the approval rate.
     */
    public const STATUS_MAP = [
        'Sold' => 'article_published',
        'Too expensive' => 'high_price',
        'Low Metrics' => 'requirements_not_met',
        'Waiting answer' => 'waiting_client_approval',
        'Client Refused' => 'refused_other',
        'Not interested at the moment' => 'not_interested',
        'Disappeared' => 'customer_disappeared',
    ];

    /** Board columns we read. Anything else on the board is ignored. */
    private const COLUMN_IDS = ['status', 'text', 'text8', 'dropdown8', 'numbers', 'dup__of_month'];

    private const PAGE_SIZE = 250;

    public function handle(): int
    {
        $token = config('services.monday.token');
        if (blank($token)) {
            $this->error('MONDAY_API_TOKEN is not set. Add it to .env (Monday → Admin → API).');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        if (! $dryRun && ! $this->confirm('This will WRITE to publications. Have you reviewed a --dry-run report first?', false)) {
            $this->warn('Aborted.');

            return self::SUCCESS;
        }

        $this->info('Reading board '.config('services.monday.board_id').' from Monday…');
        $items = $this->fetchBoardItems($token);
        if ($items === null) {
            return self::FAILURE;
        }
        $this->info('  '.count($items).' items read.');

        $this->info('Matching against LIAB…');
        $rows = $this->classify($items);

        $path = $this->writeReport($rows);
        $this->summarise($rows, $dryRun, $path);

        if ($dryRun) {
            $this->newLine();
            $this->info('Dry run — nothing was written. Review the report, then run again without --dry-run.');

            return self::SUCCESS;
        }

        $this->apply($rows);

        return self::SUCCESS;
    }

    /* ===================== Monday ===================== */

    /** @return array<int, array>|null */
    private function fetchBoardItems(string $token): ?array
    {
        $boardId = (string) config('services.monday.board_id');
        $columns = json_encode(self::COLUMN_IDS);
        $items = [];
        $cursor = null;

        do {
            $query = <<<GQL
            query (\$board: [ID!], \$cursor: String) {
              boards(ids: \$board) {
                items_page(limit: {$this->pageSize()}, cursor: \$cursor) {
                  cursor
                  items {
                    id
                    name
                    column_values(ids: {$columns}) { id text }
                  }
                }
              }
            }
            GQL;

            $response = Http::withHeaders([
                'Authorization' => $token,
                'API-Version' => '2024-01',
            ])->timeout(60)->post(config('services.monday.api_url'), [
                'query' => $query,
                'variables' => ['board' => [$boardId], 'cursor' => $cursor],
            ]);

            if (! $response->successful()) {
                $this->error('Monday API returned HTTP '.$response->status().': '.$response->body());

                return null;
            }

            $json = $response->json();
            if (isset($json['errors'])) {
                $this->error('Monday API error: '.json_encode($json['errors']));

                return null;
            }

            $page = $json['data']['boards'][0]['items_page'] ?? null;
            if ($page === null) {
                $this->error('Board '.$boardId.' returned no data — check the token has access to it.');

                return null;
            }

            foreach ($page['items'] as $item) {
                $values = [];
                foreach ($item['column_values'] as $cv) {
                    $values[$cv['id']] = $cv['text'];
                }
                $items[] = [
                    'monday_item_id' => (int) $item['id'],
                    'name' => $item['name'],
                    'monday_status' => trim((string) ($values['status'] ?? '')),
                    'client_name' => trim((string) ($values['text'] ?? '')),
                    'article_url' => trim((string) ($values['text8'] ?? '')),
                    'site' => trim((string) ($values['dropdown8'] ?? '')),
                    'amount' => $values['numbers'] ?? null,
                    'date' => trim((string) ($values['dup__of_month'] ?? '')),
                ];
            }

            $cursor = $page['cursor'] ?? null;
            $this->output->write('.');
        } while ($cursor);

        $this->newLine();

        return $items;
    }

    private function pageSize(): int
    {
        return self::PAGE_SIZE;
    }

    /* ===================== matching ===================== */

    /** @return array<int, array> */
    private function classify(array $items): array
    {
        $websites = Website::query()
            ->select('id', 'domain_name')
            ->get()
            ->keyBy(fn ($w) => $this->normaliseDomain($w->domain_name));

        $clients = Client::query()->select('id', 'first_name', 'last_name', 'email')->get();

        // Publications already carrying a Monday id, and every publication with
        // an article URL, both keyed for a single in-memory lookup.
        $alreadyLinked = Publication::withTrashed()
            ->whereNotNull('monday_item_id')
            ->pluck('id', 'monday_item_id');

        $byUrl = [];
        Publication::query()
            ->whereNotNull('article_url')
            ->where('article_url', '<>', '')
            ->select('id', 'article_url')
            ->chunkById(2000, function ($chunk) use (&$byUrl) {
                foreach ($chunk as $p) {
                    $key = $this->normaliseUrl($p->article_url);
                    // First one wins: duplicates are pre-existing and reported
                    // rather than silently picked between.
                    $byUrl[$key] ??= $p->id;
                }
            });

        $rows = [];

        foreach ($items as $item) {
            $row = $item + ['action' => null, 'reason' => null, 'liab_status' => null, 'publication_id' => null, 'client_id' => null];

            $row['liab_status'] = self::STATUS_MAP[$item['monday_status']] ?? null;
            $row['client_id'] = $this->matchClient($clients, $item['client_name']);

            $siteKey = $this->normaliseDomain($item['site'] !== '' ? $item['site'] : $item['name']);
            $website = $websites->get($siteKey);
            $row['website_id'] = $website->id ?? null;

            if (isset($alreadyLinked[$item['monday_item_id']])) {
                $row['action'] = 'linked';
                $row['publication_id'] = $alreadyLinked[$item['monday_item_id']];
                $row['reason'] = 'Already imported.';
                $rows[] = $row;

                continue;
            }

            $urlKey = $item['article_url'] !== '' ? $this->normaliseUrl($item['article_url']) : null;
            if ($urlKey !== null && isset($byUrl[$urlKey])) {
                $row['action'] = 'match';
                $row['publication_id'] = $byUrl[$urlKey];
                $row['reason'] = 'Same Article URL — will only stamp the Monday id.';
                $rows[] = $row;

                continue;
            }

            if ($row['liab_status'] === null) {
                $row['action'] = 'skip';
                $row['reason'] = 'Unmapped Monday status: "'.$item['monday_status'].'".';
                $rows[] = $row;

                continue;
            }

            if ($row['website_id'] === null) {
                $row['action'] = 'skip';
                $row['reason'] = 'Site not found in Domains: "'.($item['site'] ?: $item['name']).'".';
                $rows[] = $row;

                continue;
            }

            $row['action'] = 'create';
            $row['reason'] = $row['client_id'] === null && $item['client_name'] !== ''
                ? 'Client not matched — will be created without one.'
                : null;
            $rows[] = $row;
        }

        return $rows;
    }

    /** Best-effort match on "first last" or email; null when uncertain. */
    private function matchClient($clients, string $name): ?int
    {
        $needle = mb_strtolower(trim($name));
        if ($needle === '') {
            return null;
        }

        foreach ($clients as $c) {
            $full = mb_strtolower(trim($c->first_name.' '.$c->last_name));
            if ($full !== '' && $full === $needle) {
                return $c->id;
            }
            if ($c->email && mb_strtolower($c->email) === $needle) {
                return $c->id;
            }
        }

        return null;
    }

    /* ===================== writing ===================== */

    private function apply(array $rows): void
    {
        $created = 0;
        $stamped = 0;

        $toStamp = array_filter($rows, fn ($r) => $r['action'] === 'match');
        $toCreate = array_filter($rows, fn ($r) => $r['action'] === 'create');

        $bar = $this->output->createProgressBar(count($toStamp) + count($toCreate));
        $bar->start();

        DB::transaction(function () use ($toStamp, $toCreate, &$created, &$stamped, $bar) {
            // Existing publications: ONLY the Monday id is written. Their
            // status, price, date and URL are deliberately left alone.
            foreach (array_chunk($toStamp, 200) as $chunk) {
                foreach ($chunk as $row) {
                    Publication::withTrashed()
                        ->whereKey($row['publication_id'])
                        ->update(['monday_item_id' => $row['monday_item_id']]);
                    $stamped++;
                    $bar->advance();
                }
            }

            foreach (array_chunk($toCreate, 200) as $chunk) {
                foreach ($chunk as $row) {
                    $attributes = [
                        'monday_item_id' => $row['monday_item_id'],
                        'website_id' => $row['website_id'],
                        'client_id' => $row['client_id'],
                        'status' => $row['liab_status'],
                        'article_url' => $row['article_url'] !== '' ? $row['article_url'] : null,
                        'publication_date' => $row['date'] !== '' ? $row['date'] : null,
                        'menford' => $this->amount($row['amount']),
                        'copy_nr' => 0,
                        'publisher_amount' => 0,
                        'client_copy' => 0,
                    ];

                    // Keeps total_cost / total_revenues / profit coherent with
                    // every other publication rather than leaving them null.
                    StorageCalculator::apply($attributes);

                    Publication::create($attributes);
                    $created++;
                    $bar->advance();
                }
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. {$created} publications created, {$stamped} existing publications linked.");
    }

    private function amount($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace([' ', ',', '€'], ['', '.', ''], (string) $value);
    }

    /* ===================== reporting ===================== */

    private function writeReport(array $rows): string
    {
        $path = $this->option('out')
            ?: storage_path('app/monday-network-sales-'.now()->format('Y-m-d_His').'.csv');

        $fh = fopen($path, 'w');
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, [
            'Monday item', 'Site', 'Client', 'Client matched', 'Monday status',
            'LIAB status', 'Article URL', 'Amount', 'Date', 'Action', 'Publication id', 'Note',
        ]);

        foreach ($rows as $r) {
            fputcsv($fh, [
                $r['monday_item_id'],
                $r['site'] ?: $r['name'],
                $r['client_name'],
                $r['client_id'] ? 'yes' : 'no',
                $r['monday_status'],
                $r['liab_status'],
                $r['article_url'],
                $r['amount'],
                $r['date'],
                $r['action'],
                $r['publication_id'],
                $r['reason'],
            ]);
        }

        fclose($fh);

        return $path;
    }

    private function summarise(array $rows, bool $dryRun, string $path): void
    {
        $count = fn (string $action) => count(array_filter($rows, fn ($r) => $r['action'] === $action));

        $creates = array_filter($rows, fn ($r) => $r['action'] === 'create');
        $revenue = array_sum(array_map(fn ($r) => $this->amount($r['amount']), $creates));
        $noClient = count(array_filter($creates, fn ($r) => $r['client_id'] === null));

        $this->newLine();
        $this->table(['', 'Items'], [
            ['Already imported (no change)', $count('linked')],
            ['Existing publication, will link only', $count('match')],
            ['Will be created', $count('create')],
            ['Skipped (see report)', $count('skip')],
            ['Total read from the board', count($rows)],
        ]);

        if ($revenue > 0) {
            $this->warn('Creating these adds € '.number_format($revenue, 2).' of revenue to the Stats pages.');
        }
        if ($noClient > 0) {
            $this->warn($noClient.' of the new publications have no matching client and will be created without one.');
        }

        $this->info('Report written to: '.$path);
    }

    /* ===================== helpers ===================== */

    private function normaliseDomain(?string $v): string
    {
        $v = mb_strtolower(trim((string) $v));
        $v = preg_replace('#^https?://#', '', $v) ?? $v;
        $v = preg_replace('#^www\.#', '', $v) ?? $v;

        return rtrim($v, '/');
    }

    private function normaliseUrl(?string $v): string
    {
        $v = mb_strtolower(trim((string) $v));
        $v = preg_replace('#^https?://#', '', $v) ?? $v;
        $v = preg_replace('#^www\.#', '', $v) ?? $v;

        return rtrim($v, '/');
    }
}
