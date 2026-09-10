<?php

namespace App\Console\Commands;

use App\Mail\ArticleApprovalReminderMail;
use App\Models\OrderItem;
use App\Models\PublicationStatusEvent;
use App\Models\Storage;
use App\Support\WorkingDays;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Moves marketplace placements past the deadlines that would otherwise leave
 * them — and the tokens held against them — waiting on somebody's silence.
 *
 *   waiting_blog_price_confirmation, 5 working days
 *       -> publisher_disappeared. The site is dropped and its tokens released,
 *          which OrderSettlement does on the back of the status change.
 *
 *   waiting_client_article_approval, 3 working days
 *       -> waiting_blog_publication. Silence is treated as approval so the
 *          order can finish and we can be paid.
 *
 * Clocks start when the publication ENTERED its current status, read from
 * publication_status_events. That is what makes a corrected article restart the
 * client's three days for free: it leaves the status and comes back, writing a
 * new event, and the clock reads the latest one.
 *
 * THE ORDER OF WORK MATTERS. Approval runs BEFORE reminders, and refuses to
 * publish anything that has not already been warned in this cycle. So the
 * earliest an article can be auto-published is the run after its reminder went
 * out — never the same one. An article can therefore never go live without the
 * client having been told, by email, on which date it would.
 *
 * NARROW BY DESIGN: only publications linked to a marketplace order item.
 * Martina's own CRM work and the Monday-imported rows have no customer waiting
 * and are never advanced by a machine.
 *
 * Gated behind linkbuilding.marketplace_deadlines.enabled, default false.
 * --dry-run works regardless, so the effect can be inspected before enabling.
 */
class AdvanceOverdueMarketplacePublications extends Command
{
    protected $signature = 'marketplace:advance-overdue
                            {--dry-run : Report what would change without changing it}';

    protected $description = 'Expire unconfirmed publisher prices, remind clients, and auto-approve unanswered articles';

    private const STATUS_PRICE_CONFIRMATION = 'waiting_blog_price_confirmation';

    private const STATUS_CLIENT_APPROVAL = 'waiting_client_article_approval';

    private bool $dryRun = false;

    private array $deadlines = [];

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');
        $this->deadlines = config('linkbuilding.marketplace_deadlines', []);

        if (! ($this->deadlines['enabled'] ?? false) && ! $this->dryRun) {
            $this->warn('[marketplace:advance-overdue] Disabled (linkbuilding.marketplace_deadlines.enabled). '
                .'Run with --dry-run to see what it would do.');

            return self::SUCCESS;
        }

        if ($this->dryRun) {
            $this->info('[marketplace:advance-overdue] DRY RUN — nothing will be changed.');
        }

        $expired = $this->expireUnconfirmedPrices();
        $approved = $this->autoApproveArticles();
        $reminded = $this->remindClients();

        $this->newLine();
        $this->info("[marketplace:advance-overdue] {$expired} expired, {$approved} auto-approved, {$reminded} reminded.");

        return self::SUCCESS;
    }

    /** The publisher never came back. Drop the site; the tokens go back. */
    private function expireUnconfirmedPrices(): int
    {
        $days = (int) ($this->deadlines['publisher_confirmation_working_days'] ?? 5);
        $moved = 0;

        foreach ($this->marketplacePublicationsIn(self::STATUS_PRICE_CONFIRMATION) as $publication) {
            $enteredAt = $this->enteredCurrentStatusAt($publication);

            if ($enteredAt === null || ! WorkingDays::passed($enteredAt, $days)) {
                continue;
            }

            $this->line("  publication {$publication->id}: expired after "
                .WorkingDays::elapsed($enteredAt).' working days — publisher never confirmed');

            if (! $this->dryRun) {
                $publication->forceFill(['status' => 'publisher_disappeared'])->save();
                Log::info("[marketplace:advance-overdue] publication {$publication->id} -> publisher_disappeared");
            }

            $moved++;
        }

        return $moved;
    }

    /**
     * Silence becomes approval — but only for an article whose client was
     * already warned in this cycle. An unreminded one is left for the reminder
     * pass below and picked up on the next run.
     */
    private function autoApproveArticles(): int
    {
        $days = (int) ($this->deadlines['client_article_approval_working_days'] ?? 3);
        $moved = 0;

        foreach ($this->marketplacePublicationsIn(self::STATUS_CLIENT_APPROVAL) as $publication) {
            $enteredAt = $this->enteredCurrentStatusAt($publication);

            if ($enteredAt === null || ! WorkingDays::passed($enteredAt, $days)) {
                continue;
            }

            $item = $this->orderItemFor($publication);

            if (! $item) {
                continue;
            }

            // The guarantee: nothing is published without a warning having gone
            // out for THIS draft. A reminder older than the current cycle
            // belongs to a previous version and does not count.
            if (! $this->wasRemindedThisCycle($item, $enteredAt)) {
                $this->line("  publication {$publication->id}: due for approval but not yet reminded — "
                    .'holding until the reminder has been sent');

                continue;
            }

            $this->line("  publication {$publication->id}: auto-approved after "
                .WorkingDays::elapsed($enteredAt).' working days — client did not reply');

            if (! $this->dryRun) {
                $publication->forceFill(['status' => 'waiting_blog_publication'])->save();
                Log::info("[marketplace:advance-overdue] publication {$publication->id} auto-approved");
            }

            $moved++;
        }

        return $moved;
    }

    /** Warn the client, naming the date their article would go live. */
    private function remindClients(): int
    {
        $reminderDays = (int) ($this->deadlines['client_reminder_working_days'] ?? 2);
        $approvalDays = (int) ($this->deadlines['client_article_approval_working_days'] ?? 3);
        $sent = 0;

        foreach ($this->marketplacePublicationsIn(self::STATUS_CLIENT_APPROVAL) as $publication) {
            $enteredAt = $this->enteredCurrentStatusAt($publication);

            if ($enteredAt === null || ! WorkingDays::passed($enteredAt, $reminderDays)) {
                continue;
            }

            $item = $this->orderItemFor($publication);

            if (! $item || $this->wasRemindedThisCycle($item, $enteredAt)) {
                continue;
            }

            $recipient = $item->order?->user;

            if (! $recipient?->email) {
                Log::warning("[marketplace:advance-overdue] no recipient for order item {$item->id}; reminder skipped.");

                continue;
            }

            $deadline = WorkingDays::deadline($enteredAt, $approvalDays);

            $this->line("  publication {$publication->id}: reminding {$recipient->email} "
                .'— publishes '.$deadline->toDateString());

            if (! $this->dryRun) {
                try {
                    Mail::to($recipient->email)->send(new ArticleApprovalReminderMail($item, $deadline));
                } catch (\Throwable $e) {
                    // Never stamp a reminder we failed to send: doing so would
                    // let the approval pass publish an article whose client was
                    // never actually warned.
                    Log::error("[marketplace:advance-overdue] reminder FAILED for order item {$item->id}: ".$e->getMessage());

                    continue;
                }

                $item->forceFill(['approval_reminder_sent_at' => now()])->save();
            }

            $sent++;
        }

        return $sent;
    }

    /**
     * A reminder counts only if it was sent for the CURRENT draft. One sent
     * before this cycle began belongs to a previous version of the article.
     */
    private function wasRemindedThisCycle(OrderItem $item, Carbon $cycleStartedAt): bool
    {
        return $item->approval_reminder_sent_at !== null
            && $item->approval_reminder_sent_at->greaterThanOrEqualTo($cycleStartedAt);
    }

    private function orderItemFor(Storage $publication): ?OrderItem
    {
        return OrderItem::with('order.user', 'website')
            ->where('storage_id', $publication->getKey())
            ->first();
    }

    /** @return \Illuminate\Support\Collection<int, Storage> */
    private function marketplacePublicationsIn(string $status): \Illuminate\Support\Collection
    {
        $linkedIds = OrderItem::query()->whereNotNull('storage_id')->pluck('storage_id');

        if ($linkedIds->isEmpty()) {
            return collect();
        }

        return Storage::query()->whereIn('id', $linkedIds)->where('status', $status)->get();
    }

    /**
     * When this publication entered the status it is in now — the LATEST
     * matching event, so a return to a status is timed from the return.
     */
    private function enteredCurrentStatusAt(Storage $publication): ?Carbon
    {
        $at = PublicationStatusEvent::query()
            ->where('storage_id', $publication->getKey())
            ->where('status', (string) $publication->status)
            ->orderByDesc('id')
            ->value('created_at');

        if ($at === null) {
            Log::warning("[marketplace:advance-overdue] publication {$publication->getKey()} has no status history; skipped.");

            return null;
        }

        return Carbon::parse($at);
    }
}
