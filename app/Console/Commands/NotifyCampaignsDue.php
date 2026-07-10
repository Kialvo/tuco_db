<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\CrmNotification;
use App\Services\NotificationHub;
use Illuminate\Console\Command;

/**
 * Daily reminder: campaigns whose next_update_date is today notify their
 * responsible user through the org-wide hub (mirrors SOPs' sop_update_due).
 * Idempotent per day — safe to run repeatedly.
 */
class NotifyCampaignsDue extends Command
{
    protected $signature = 'lb:notify-due {--dry-run : List due campaigns without notifying}';

    protected $description = 'Notify responsible users of campaigns whose next update is due today';

    public function handle(): int
    {
        $due = Campaign::with('responsibleUser')
            ->whereDate('next_update_date', now()->toDateString())
            ->whereNotNull('responsible_user_id')
            ->get();

        if ($due->isEmpty()) {
            $this->info('No campaigns due today.');
            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($due as $campaign) {
            $responsible = $campaign->responsibleUser;
            if (! $responsible) {
                continue;
            }

            // Idempotency: skip if this reminder already exists today.
            $already = CrmNotification::query()->fromTuco()
                ->forEmail($responsible->email)
                ->where('type', 'campaign_update_due')
                ->where('entity_type', 'campaign')
                ->where('entity_id', (string) $campaign->id)
                ->whereDate('created_at', now()->toDateString())
                ->exists();

            if ($already) {
                $this->line("  skip {$campaign->code} — already notified today");
                continue;
            }

            $this->line(sprintf('  %s %s → %s', $this->option('dry-run') ? 'WOULD notify' : 'notify', $campaign->code, $responsible->email));

            if ($this->option('dry-run')) {
                continue;
            }

            NotificationHub::notify([
                'type'           => 'campaign_update_due',
                'recipients'     => [$responsible],
                'entity_type'    => 'campaign',
                'entity_id'      => (string) $campaign->id,
                'entity_label'   => $campaign->code,
                'body'           => $campaign->code . ' is due for an update today',
                'link'           => route('crm.campaigns.index') . '?thread=' . $campaign->id,
                'from_user_name' => 'Linkinablink',
            ]);
            $sent++;
        }

        $this->info($this->option('dry-run') ? '[DRY RUN] nothing sent.' : "{$sent} notification(s) sent.");

        return self::SUCCESS;
    }
}
