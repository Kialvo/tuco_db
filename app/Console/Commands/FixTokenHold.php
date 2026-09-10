<?php

namespace App\Console\Commands;

use App\Models\OrderItem;
use App\Services\Tokens\TokenLedger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Settle a hold by hand.
 *
 * The companion to `tokens:reconcile`, which reports UNSETTLED holds but
 * deliberately repairs nothing. This is how a human acts on that report.
 *
 * It exists because settlement swallows its errors — a publication save must
 * never fail on bookkeeping — so a hold can be left open by a transient fault.
 * Without this the only fix would be SQL against production, which is exactly
 * what nobody should be doing to a ledger.
 *
 * Everything goes through TokenLedger, so the same guards apply: the ledger
 * refuses to release something already captured or capture something already
 * released, and idempotency keys make a repeated run a no-op.
 */
class FixTokenHold extends Command
{
    protected $signature = 'tokens:fix-hold
                            {item : The order_items id, as reported by tokens:reconcile}
                            {--capture : The article went live — charge for it}
                            {--release= : Give the tokens back, with a reason (publisher_refused, publisher_disappeared, cancelled, admin)}
                            {--dry-run : Show what would happen without doing it}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Manually capture or release a stuck token hold';

    public function handle(TokenLedger $ledger): int
    {
        $item = OrderItem::with('order.user', 'website')->find($this->argument('item'));

        if (! $item) {
            $this->error("Order item {$this->argument('item')} does not exist.");

            return self::FAILURE;
        }

        $capture = (bool) $this->option('capture');
        $release = $this->option('release');

        if ($capture === ($release !== null)) {
            $this->error('Choose exactly one of --capture or --release=<reason>.');

            return self::FAILURE;
        }

        if (! $item->isHeld()) {
            $this->warn("Order item {$item->id} has no open hold — it is "
                .($item->isCaptured() ? 'already captured' : ($item->isReleased() ? 'already released' : 'not held'))
                .'. Nothing to do.');

            return self::SUCCESS;
        }

        $user = $item->order?->user;

        if (! $user) {
            $this->error("Order item {$item->id} has no user behind it; cannot resolve a wallet.");

            return self::FAILURE;
        }

        $domain = $item->website?->domain_name ?? '(unknown site)';
        $action = $capture ? 'CAPTURE' : "RELEASE ({$release})";

        $this->line("Order item {$item->id} — {$domain}");
        $this->line("  order    : {$item->order_id}");
        $this->line("  customer : {$user->email}");
        $this->line("  held     : {$item->tokens_held} tokens since {$item->held_at}");
        $this->line("  action   : {$action}");

        if ($this->option('dry-run')) {
            $this->info('DRY RUN — nothing changed.');

            return self::SUCCESS;
        }

        // Money, by hand, on a live ledger. Worth one deliberate keystroke —
        // unless --force, for scripting and for tests.
        if (! $this->option('force') && ! $this->confirm('Apply this?', false)) {
            $this->line('Cancelled.');

            return self::SUCCESS;
        }

        $account = $ledger->accountFor($user);

        try {
            if ($capture) {
                $ledger->captureHold($account, $item);
            } else {
                $ledger->releaseHold($account, $item, (string) $release);
            }
        } catch (\Throwable $e) {
            $this->error('Refused: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Done. Balance is now {$ledger->balance($user)} tokens.");

        Log::warning("[tokens] MANUAL {$action} on order item {$item->id} by console "
            ."({$item->tokens_held} tokens, customer {$user->email})");

        return self::SUCCESS;
    }
}
