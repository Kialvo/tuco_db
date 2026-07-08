<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Phase 3 / D1 — normalize legacy storage.status variants to the unified
 * slug list. Only touches the exact legacy strings below; rows already on
 * valid slugs, plus '0'/NULL/empty, are left completely untouched.
 *
 * ALWAYS run with --dry-run first. A JSON audit file (old value + ids) is
 * written to storage/app before any update, so every change is reversible.
 */
class NormalizeStorageStatuses extends Command
{
    protected $signature = 'lb:normalize-storage-statuses {--dry-run : Report what would change without writing}';

    protected $description = 'Normalize legacy storage.status values to the unified Phase-3 slug list';

    /** exact legacy value (collation-insensitive match) => unified slug */
    private const MAP = [
        'Requirements not met'                 => 'requirements_not_met',
        'Publisher Refused'                    => 'publisher_refused',
        'Already used by client'               => 'already_used_by_client',
        'Publisher Disappeared'                => 'publisher_disappeared',
        'Out of topic'                         => 'out_of_topic',
        'High price'                           => 'high_price',
        'Waiting for Client Blog Approval'     => 'waiting_blog_publication',
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $this->info(($dry ? '[DRY RUN] ' : '') . 'Normalizing legacy storage.status values…');

        $audit = [];
        $total = 0;

        foreach (self::MAP as $old => $new) {
            // BINARY-insensitive default collation also catches case variants,
            // but never the underscore slugs themselves (different strings).
            // Exclude rows already holding the target slug to be safe.
            $query = DB::table('storage')
                ->where('status', $old)
                ->where('status', '!=', $new);

            $ids = $query->pluck('id')->all();

            if (! count($ids)) {
                continue;
            }

            $this->line(sprintf('  %-40s → %-32s %d row(s)', '"' . $old . '"', $new, count($ids)));
            $audit[] = ['from' => $old, 'to' => $new, 'ids' => $ids];
            $total += count($ids);

            if (! $dry) {
                DB::table('storage')->whereIn('id', $ids)->update(['status' => $new]);
            }
        }

        if (! $total) {
            $this->info('Nothing to normalize.');
            return self::SUCCESS;
        }

        if ($dry) {
            $this->info("[DRY RUN] {$total} row(s) WOULD be updated. Re-run without --dry-run to apply.");
        } else {
            $file = 'lb_status_normalization_' . now()->format('Ymd_His') . '.json';
            \Illuminate\Support\Facades\Storage::disk('local')->put($file, json_encode($audit, JSON_PRETTY_PRINT));
            $this->info("{$total} row(s) updated. Audit log: storage/app/{$file}");
        }

        // Report what remains outside the unified list (left untouched by design)
        $slugs = \App\Support\PublicationStatus::slugs();
        $left = DB::table('storage')
            ->select('status', DB::raw('count(*) c'))
            ->where(fn ($q) => $q->whereNotIn('status', $slugs)->orWhereNull('status'))
            ->groupBy('status')->orderByDesc('c')->get();

        if ($left->count()) {
            $this->warn('Values left untouched (empty/legacy, rendered as "—" in the UI):');
            foreach ($left as $row) {
                $this->line(sprintf('  %-40s %d row(s)', var_export($row->status, true), $row->c));
            }
        }

        return self::SUCCESS;
    }
}
