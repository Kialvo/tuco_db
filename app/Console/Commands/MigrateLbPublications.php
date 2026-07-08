<?php

namespace App\Console\Commands;

use App\Models\PublicationComment;
use App\Models\Storage;
use App\Models\User;
use App\Models\Website;
use App\Services\StorageCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Phase 3 / D2 — one-off: convert legacy lb_publications rows into `storage`
 * rows (the storage row IS the publication now). Demo campaigns (code
 * starting with DEMO) are skipped. The lb_publications table is left in
 * place, untouched, and will be dropped in a later cleanup PR.
 *
 * ALWAYS run with --dry-run first.
 */
class MigrateLbPublications extends Command
{
    protected $signature = 'lb:migrate-lb-publications {--dry-run : Report what would be created without writing}';

    protected $description = 'Convert legacy lb_publications rows into linked storage rows (Phase 3)';

    /** legacy display-string statuses (Phase 1/2) => unified slug */
    private const STATUS_MAP = [
        'Waiting Client Approval'             => 'waiting_client_approval',
        'Accepted'                            => 'accepted',
        'Refused by Client – Metrics Too Low' => 'requirements_not_met',
        'Refused by Client – Too Expensive'   => 'high_price',
        'Refused by Client – Out of Topic'    => 'out_of_topic',
        'Waiting Copywriter'                  => 'waiting_copywriter',
        'Waiting Client Article Approval'     => 'waiting_client_article_approval',
        'Waiting Blog Publication'            => 'waiting_blog_publication',
        'Published'                           => 'article_published',
        'Publisher disappeared'               => 'publisher_disappeared',
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $this->info(($dry ? '[DRY RUN] ' : '') . 'Migrating lb_publications → storage…');

        $rows = DB::table('lb_publications')
            ->leftJoin('lb_campaigns', 'lb_campaigns.id', '=', 'lb_publications.lb_campaign_id')
            ->whereNull('lb_publications.deleted_at')
            ->select('lb_publications.*', 'lb_campaigns.code as campaign_code', 'lb_campaigns.responsible_user_id', 'lb_campaigns.deleted_at as campaign_deleted_at')
            ->orderBy('lb_publications.id')
            ->get();

        $fallbackUserId = User::where('role', 'admin')->orderBy('id')->value('id');
        $audit = [];

        foreach ($rows as $p) {
            $tag = "#{$p->id} {$p->site} [{$p->campaign_code}]";

            if (! $p->campaign_code || $p->campaign_deleted_at) {
                $this->warn("  SKIP {$tag} — campaign missing or deleted");
                continue;
            }
            if (str_starts_with($p->campaign_code, 'DEMO')) {
                $this->line("  skip {$tag} — demo campaign");
                continue;
            }

            $status = self::STATUS_MAP[$p->status] ?? null;
            if ($p->status && ! $status) {
                $this->warn("  SKIP {$tag} — unmapped status \"{$p->status}\"");
                continue;
            }

            $websiteId = Website::where('domain_name', $p->site)->value('id');

            $attrs = [
                'lb_campaign_id'             => $p->lb_campaign_id,
                'campaign_code'              => $p->campaign_code,
                'website_id'                 => $websiteId,
                'website'                    => $websiteId ? null : $p->site,
                'status'                     => $status,
                'article_url'                => $p->live_url,
                'publication_date'           => $p->live_date,
                'copywriter_commision_date'  => $p->date_to_copywriter,
                'copywriter_submission_date' => $p->date_from_copywriter,
                'article_sent_to_publisher'  => $p->date_to_blog,
            ];
            StorageCalculator::setPrice($attrs, (float) $p->price);

            $this->line(sprintf(
                '  %s %-40s → storage (website_id: %s, status: %s, price €%s%s)',
                $dry ? 'WOULD create' : 'create',
                $tag,
                $websiteId ?? 'free-text',
                $status ?? '—',
                number_format((float) $p->price, 2),
                $p->notes ? ', notes → comment' : ''
            ));

            if ($dry) {
                continue;
            }

            $storage = Storage::create($attrs);

            if ($p->notes) {
                PublicationComment::create([
                    'storage_id' => $storage->id,
                    'user_id'    => $p->responsible_user_id ?? $fallbackUserId,
                    'body'       => '[migrated note] ' . $p->notes,
                ]);
            }

            $audit[] = ['lb_publication_id' => $p->id, 'storage_id' => $storage->id];
        }

        if (! $dry && count($audit)) {
            $file = 'lb_publications_migration_' . now()->format('Ymd_His') . '.json';
            \Illuminate\Support\Facades\Storage::disk('local')->put($file, json_encode($audit, JSON_PRETTY_PRINT));
            $this->info(count($audit) . ' storage row(s) created. Audit log: storage/app/' . $file);
        } elseif ($dry) {
            $this->info('[DRY RUN] nothing written.');
        } else {
            $this->info('Nothing migrated.');
        }

        return self::SUCCESS;
    }
}
