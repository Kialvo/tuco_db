<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off: copy legacy flat comments into the CRM-style lb_updates table.
 * The old tables (lb_campaign_comments, lb_publication_comments) are left
 * UNTOUCHED with all their rows — they act as the backup dump until a later
 * cleanup PR drops them (same treatment as lb_publications).
 *
 * Idempotent: a row is skipped if an identical update already exists.
 * ALWAYS run with --dry-run first.
 */
class MigrateCommentsToUpdates extends Command
{
    protected $signature = 'lb:migrate-comments {--dry-run : Report what would be copied without writing}';

    protected $description = 'Copy legacy lb_*_comments rows into lb_updates (old tables stay untouched)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $this->info(($dry ? '[DRY RUN] ' : '') . 'Copying legacy comments → lb_updates…');

        $sources = [
            ['table' => 'lb_campaign_comments',    'entity_type' => 'campaign',    'entity_col' => 'lb_campaign_id'],
            ['table' => 'lb_publication_comments', 'entity_type' => 'publication', 'entity_col' => 'storage_id'],
        ];

        $copied = 0;
        $skipped = 0;

        foreach ($sources as $src) {
            $rows = DB::table($src['table'])->orderBy('id')->get();
            $this->line("  {$src['table']}: {$rows->count()} row(s)");

            foreach ($rows as $row) {
                $entityId = (string) $row->{$src['entity_col']};

                $exists = DB::table('lb_updates')
                    ->where('entity_type', $src['entity_type'])
                    ->where('entity_id', $entityId)
                    ->where('user_id', $row->user_id)
                    ->where('body', $row->body)
                    ->where('created_at', $row->created_at)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $this->line(sprintf(
                    '    %s #%d [%s:%s] %s',
                    $dry ? 'WOULD copy' : 'copy',
                    $row->id,
                    $src['entity_type'],
                    $entityId,
                    mb_substr($row->body, 0, 60)
                ));

                if (! $dry) {
                    DB::table('lb_updates')->insert([
                        'entity_type' => $src['entity_type'],
                        'entity_id'   => $entityId,
                        'user_id'     => $row->user_id,
                        'body'        => $row->body,
                        'created_at'  => $row->created_at,
                        'updated_at'  => $row->updated_at ?? $row->created_at,
                    ]);
                }
                $copied++;
            }
        }

        $this->info(($dry ? '[DRY RUN] ' : '') . "{$copied} copied, {$skipped} already present. Old tables untouched (backup).");

        return self::SUCCESS;
    }
}
