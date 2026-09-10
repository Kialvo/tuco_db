<?php

namespace App\Console\Commands;

use App\Models\OrderItem;
use App\Models\TokenAccount;
use App\Models\TokenTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Re-derives the truth and complains when reality disagrees with it.
 *
 * This command is the reason OrderSettlement is allowed to swallow errors. A
 * publication save must not fail because of bookkeeping, so settlement logs
 * and continues — which is only defensible if something else notices when a
 * settlement silently didn't happen. That something is this.
 *
 * READ ONLY. It reports and never repairs: a wrong automatic correction to a
 * ledger is far more expensive than a human reading a report. Every finding
 * here is meant to be acted on deliberately.
 *
 * Findings, worst first:
 *
 *   DRIFT      balance_cached disagrees with SUM(amount). Should be
 *              impossible — they are written in one transaction — so it means
 *              something wrote the cache directly. Critical.
 *   ORPHANED   an item marked held with no hold row, or a hold row with no
 *              mark. The two are written together, so a mismatch is a bug.
 *   UNSETTLED  a publication that is live, or dead, while its tokens are still
 *              held. This is exactly what a swallowed settlement failure looks
 *              like: work delivered and never charged for.
 *   STALE      a hold older than --stale-days. Not a bug — unlimited article
 *              revisions make long holds legitimate — but nobody should learn
 *              about a four-month-old hold by accident.
 */
class ReconcileTokens extends Command
{
    protected $signature = 'tokens:reconcile
                            {--stale-days=45 : Flag holds older than this many days}
                            {--quiet-when-clean : Print nothing when everything agrees}';

    protected $description = 'Verify token balances against the ledger and report anything that disagrees';

    /** Publication statuses that should have settled the hold by now. */
    private const SETTLED_STATUSES = [
        'article_published',
        'publisher_refused',
        'publisher_disappeared',
    ];

    public function handle(): int
    {
        $problems = 0;

        $problems += $this->reportDrift();
        $problems += $this->reportOrphanedHolds();
        $problems += $this->reportUnsettled();

        $this->reportStale();   // informational — never a failure

        if ($problems === 0) {
            if (! $this->option('quiet-when-clean')) {
                $this->info('[tokens:reconcile] Ledger and balances agree. Nothing to report.');
            }

            return self::SUCCESS;
        }

        // Non-zero so a scheduler or CI treats it as needing attention.
        $this->newLine();
        $this->error("[tokens:reconcile] {$problems} problem(s) found. Nothing was changed — these need a human.");

        return self::FAILURE;
    }

    /**
     * The cached balance must equal the sum of the account's ledger rows.
     * They are written inside one transaction, so drift means something
     * bypassed TokenLedger entirely.
     */
    private function reportDrift(): int
    {
        $drifted = TokenAccount::query()
            ->select('token_accounts.id', 'token_accounts.user_id', 'token_accounts.balance_cached')
            ->selectSub(
                TokenTransaction::selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('token_account_id', 'token_accounts.id'),
                'ledger_sum'
            )
            ->get()
            ->filter(fn ($a) => (int) $a->balance_cached !== (int) $a->ledger_sum);

        if ($drifted->isEmpty()) {
            return 0;
        }

        $this->error('DRIFT — cached balance disagrees with the ledger:');

        foreach ($drifted as $account) {
            $line = "  account {$account->id} (user {$account->user_id}): "
                ."cached {$account->balance_cached}, ledger {$account->ledger_sum}";

            $this->line($line);
            Log::error('[tokens:reconcile] DRIFT '.$line);
        }

        return $drifted->count();
    }

    /**
     * held_at and the hold ledger row are written in the same transaction, so
     * one without the other means a bug or a partial write.
     */
    private function reportOrphanedHolds(): int
    {
        $found = 0;

        // Marked held, but no hold row references the item.
        $missingRow = OrderItem::query()
            ->whereNotNull('held_at')
            ->whereNull('captured_at')
            ->whereNull('released_at')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('token_transactions')
                    ->where('type', TokenTransaction::TYPE_HOLD)
                    ->where('reference_type', OrderItem::class)
                    ->whereColumn('reference_id', 'order_items.id');
            })
            ->pluck('id');

        foreach ($missingRow as $id) {
            $this->error("ORPHANED — order item {$id} is marked held but has no hold row.");
            Log::error("[tokens:reconcile] ORPHANED item {$id}: held_at set, no ledger row");
            $found++;
        }

        // A hold row whose item was never marked.
        $missingMark = TokenTransaction::query()
            ->where('type', TokenTransaction::TYPE_HOLD)
            ->where('reference_type', OrderItem::class)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('order_items')
                    ->whereColumn('order_items.id', 'token_transactions.reference_id')
                    ->whereNotNull('held_at');
            })
            ->pluck('reference_id');

        foreach ($missingMark as $id) {
            $this->error("ORPHANED — a hold row exists for order item {$id}, which is not marked held.");
            Log::error("[tokens:reconcile] ORPHANED item {$id}: ledger row, no held_at");
            $found++;
        }

        return $found;
    }

    /**
     * The important one. A publication that has reached a terminal status while
     * its tokens are still held means settlement did not run or failed — work
     * delivered and never charged for, or a customer's money stuck on a site
     * that already fell through.
     */
    private function reportUnsettled(): int
    {
        $unsettled = OrderItem::query()
            ->join('storage', 'storage.id', '=', 'order_items.storage_id')
            ->whereNotNull('order_items.held_at')
            ->whereNull('order_items.captured_at')
            ->whereNull('order_items.released_at')
            ->whereIn('storage.status', self::SETTLED_STATUSES)
            ->get([
                'order_items.id',
                'order_items.tokens_held',
                'order_items.order_id',
                'storage.status as publication_status',
            ]);

        if ($unsettled->isEmpty()) {
            return 0;
        }

        $this->error('UNSETTLED — publication finished but the hold is still open:');

        foreach ($unsettled as $item) {
            $line = "  order item {$item->id} (order {$item->order_id}): "
                ."{$item->tokens_held} tokens held, publication is '{$item->publication_status}'";

            $this->line($line);
            Log::error('[tokens:reconcile] UNSETTLED '.$line);
        }

        return $unsettled->count();
    }

    /**
     * Long holds are legitimate — a client may request revisions indefinitely,
     * and the clock restarts each time — so this is visibility, not an alarm.
     */
    private function reportStale(): void
    {
        $days = max(1, (int) $this->option('stale-days'));

        $stale = OrderItem::query()
            ->whereNotNull('held_at')
            ->whereNull('captured_at')
            ->whereNull('released_at')
            ->where('held_at', '<=', now()->subDays($days))
            ->orderBy('held_at')
            ->get(['id', 'order_id', 'tokens_held', 'held_at']);

        if ($stale->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->warn("STALE — {$stale->count()} hold(s) older than {$days} days (informational):");

        foreach ($stale as $item) {
            $this->line("  order item {$item->id} (order {$item->order_id}): "
                ."{$item->tokens_held} tokens held since {$item->held_at->toDateString()}");
        }

        $this->line('  Total still committed: '.number_format($stale->sum('tokens_held')).' tokens');
    }
}
