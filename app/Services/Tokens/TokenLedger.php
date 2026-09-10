<?php

namespace App\Services\Tokens;

use App\Exceptions\InsufficientTokens;
use App\Models\OrderItem;
use App\Models\TokenAccount;
use App\Models\TokenTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The only way tokens are allowed to move.
 *
 * Two invariants this class exists to protect:
 *
 *  1. EVERY movement is an append-only ledger row. Nothing anywhere else may
 *     write token_accounts.balance_cached — it is maintained here, inside the
 *     same transaction as the row that justifies it.
 *
 *  2. EVERY movement carries an idempotency key with a UNIQUE index behind it.
 *     A retried webhook, a double-clicked button and a replayed queue job all
 *     resolve to the same key, so the second attempt returns the original row
 *     instead of moving tokens again.
 *
 * Debits additionally take a row lock: without it two concurrent spends can
 * both pass the balance check and overdraw the account.
 */
class TokenLedger
{
    /** Get (or create) the account for a user. */
    public function accountFor(User $user): TokenAccount
    {
        return TokenAccount::firstOrCreate(
            ['user_id' => $user->id],
            ['balance_cached' => 0],
        );
    }

    public function balance(User $user): int
    {
        return (int) $this->accountFor($user)->balance_cached;
    }

    /**
     * Add tokens. Used for purchases, bonuses, refunds and manual corrections.
     *
     * @param  int  $amount  positive number of tokens
     */
    public function credit(
        TokenAccount $account,
        int $amount,
        string $type,
        string $idempotencyKey,
        ?Model $reference = null,
        array $metadata = [],
        ?User $actor = null,
    ): TokenTransaction {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('credit() requires a positive amount; got '.$amount);
        }

        return $this->record($account, $amount, $type, $idempotencyKey, $reference, $metadata, $actor);
    }

    /**
     * Remove tokens. Fails loudly rather than letting a balance go negative.
     *
     * @param  int  $amount  positive number of tokens to remove
     *
     * @throws InsufficientTokens
     */
    public function debit(
        TokenAccount $account,
        int $amount,
        string $type,
        string $idempotencyKey,
        ?Model $reference = null,
        array $metadata = [],
        ?User $actor = null,
    ): TokenTransaction {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('debit() requires a positive amount; got '.$amount);
        }

        return $this->record($account, -$amount, $type, $idempotencyKey, $reference, $metadata, $actor);
    }

    /** Convenience: spend against an order. */
    public function spendForOrder(TokenAccount $account, int $tokens, Model $order, ?User $actor = null): TokenTransaction
    {
        return $this->debit(
            $account,
            $tokens,
            TokenTransaction::TYPE_SPEND,
            'order:'.$order->getKey().':spend',
            $order,
            [],
            $actor,
        );
    }

    /** Convenience: give the tokens back when an order is cancelled. */
    public function refundOrder(TokenAccount $account, int $tokens, Model $order, ?User $actor = null): TokenTransaction
    {
        return $this->credit(
            $account,
            $tokens,
            TokenTransaction::TYPE_REFUND,
            'order:'.$order->getKey().':refund',
            $order,
            [],
            $actor,
        );
    }

    /* ─────────────────────────── holds ───────────────────────────
     |
     | A hold is a real DEBIT, not a flag on the side. That is the whole
     | design: `balance_cached` therefore always means "tokens this customer
     | can actually spend", so committed tokens can never be spent twice and
     | no second source of truth exists to drift from the ledger.
     |
     | Holds are PER SITE. One publisher refusing out of five releases only
     | its own tokens and leaves the rest of the order running.
     |
     | Lifecycle: hold -> capture (published, becomes revenue)
     |            hold -> release (fell through, tokens go back)
     */

    /**
     * Commit an ordered site's tokens. Throws InsufficientTokens rather than
     * letting an order be placed against money that is not there.
     */
    public function holdForItem(TokenAccount $account, OrderItem $item, ?User $actor = null): TokenTransaction
    {
        $tokens = $item->tokenCost();

        if ($tokens <= 0) {
            throw new \InvalidArgumentException("Order item {$item->id} has no price to hold.");
        }

        return DB::transaction(function () use ($account, $item, $tokens, $actor) {
            $transaction = $this->debit(
                $account,
                $tokens,
                TokenTransaction::TYPE_HOLD,
                'order_item:'.$item->getKey().':hold',
                $item,
                ['website_id' => $item->website_id, 'order_id' => $item->order_id],
                $actor,
            );

            // Guarded so a replay cannot re-stamp the date or, worse, overwrite
            // tokens_held with a price that has moved since.
            if ($item->held_at === null) {
                $item->forceFill(['tokens_held' => $tokens, 'held_at' => now()])->save();
            }

            return $transaction;
        });
    }

    /**
     * Give a hold back. Safe to call twice; refuses outright on a captured
     * item, because crediting tokens for a placement already published and
     * paid for is a free placement.
     *
     * @return TokenTransaction|null null when there was nothing to release
     */
    public function releaseHold(TokenAccount $account, OrderItem $item, string $reason, ?User $actor = null): ?TokenTransaction
    {
        if ($item->isCaptured()) {
            throw new \LogicException(
                "Refusing to release order item {$item->id}: it was already captured at {$item->captured_at}. "
                .'Reversing a published placement is a credit note, not a release.'
            );
        }

        if (! $item->isHeld()) {
            return null;   // never held, or already released — a no-op, not an error
        }

        return DB::transaction(function () use ($account, $item, $reason, $actor) {
            $transaction = $this->credit(
                $account,
                (int) $item->tokens_held,
                TokenTransaction::TYPE_RELEASE,
                'order_item:'.$item->getKey().':release',
                $item,
                ['reason' => $reason, 'order_id' => $item->order_id],
                $actor,
            );

            $item->forceFill(['released_at' => now(), 'release_reason' => $reason])->save();

            return $transaction;
        });
    }

    /**
     * The placement went live: the hold becomes revenue.
     *
     * Writes TWO rows that net to zero — the hold is released, then spent.
     * The balance does not move, and that is the point: the money already
     * left the spendable balance when it was held. What this buys is a ledger
     * that states plainly WHEN a commitment became earned revenue, which is
     * exactly the line finance needs and which a lone `hold` row cannot give.
     *
     * @return TokenTransaction|null the spend row, or null if already captured
     */
    public function captureHold(TokenAccount $account, OrderItem $item, ?User $actor = null): ?TokenTransaction
    {
        if ($item->isCaptured()) {
            return null;   // idempotent: a replayed publication event
        }

        if ($item->isReleased()) {
            throw new \LogicException(
                "Refusing to capture order item {$item->id}: its hold was released at {$item->released_at}. "
                .'The customer already has these tokens back.'
            );
        }

        if (! $item->isHeld()) {
            throw new \LogicException("Cannot capture order item {$item->id}: nothing was ever held for it.");
        }

        return DB::transaction(function () use ($account, $item, $actor) {
            $tokens = (int) $item->tokens_held;

            $this->credit(
                $account,
                $tokens,
                TokenTransaction::TYPE_RELEASE,
                'order_item:'.$item->getKey().':capture-release',
                $item,
                ['capture' => true],
                $actor,
            );

            $spend = $this->debit(
                $account,
                $tokens,
                TokenTransaction::TYPE_SPEND,
                'order_item:'.$item->getKey().':spend',
                $item,
                ['website_id' => $item->website_id, 'order_id' => $item->order_id],
                $actor,
            );

            $item->forceFill(['captured_at' => now()])->save();

            return $spend;
        });
    }

    /**
     * Commit every site in an order, or none of them.
     *
     * The balance is checked for the WHOLE order first, purely so the customer
     * is told the real shortfall rather than whatever happened to be left when
     * the third site ran out. holdForItem() re-checks per item regardless, so
     * this is a better error message rather than the actual protection.
     *
     * Caller wraps this in its own transaction alongside the status change: a
     * half-held order whose status already says 'submitted' is worse than a
     * rejected one, because nothing downstream would know.
     *
     * @return int tokens committed
     *
     * @throws InsufficientTokens
     */
    public function holdForOrder(TokenAccount $account, Model $order, ?User $actor = null): int
    {
        $items = $order->items;
        $required = $items->sum(fn (OrderItem $item) => $item->tokenCost());
        $balance = (int) $account->fresh()->balance_cached;

        if ($balance < $required) {
            throw new InsufficientTokens($balance, $required);
        }

        foreach ($items as $item) {
            $this->holdForItem($account, $item, $actor);
        }

        return $required;
    }

    /**
     * Tokens currently committed to sites still in flight.
     *
     * Derived from the items themselves rather than cached: a stale "on hold"
     * figure would misreport what a customer can spend, and this is a small,
     * indexed query against one account's open items.
     */
    public function heldTotal(TokenAccount $account): int
    {
        return (int) OrderItem::query()
            ->whereNotNull('held_at')
            ->whereNull('captured_at')
            ->whereNull('released_at')
            ->whereHas('order', fn ($q) => $q->where('user_id', $account->user_id))
            ->sum('tokens_held');
    }

    /**
     * The single write path.
     *
     * Locks the account row, re-reads the balance under that lock, appends the
     * ledger entry and updates the cache — all in one transaction, so a crash
     * can never leave the two disagreeing.
     */
    private function record(
        TokenAccount $account,
        int $signedAmount,
        string $type,
        string $idempotencyKey,
        ?Model $reference,
        array $metadata,
        ?User $actor,
    ): TokenTransaction {
        // Outside the transaction: a replay is the common case, not an error,
        // and it should not pay for a lock.
        if ($existing = TokenTransaction::where('idempotency_key', $idempotencyKey)->first()) {
            return $existing;
        }

        return DB::transaction(function () use ($account, $signedAmount, $type, $idempotencyKey, $reference, $metadata, $actor) {
            /** @var TokenAccount $locked */
            $locked = TokenAccount::whereKey($account->getKey())->lockForUpdate()->firstOrFail();

            // Re-check under the lock: a concurrent request may have inserted
            // the same key between the check above and this transaction.
            if ($existing = TokenTransaction::where('idempotency_key', $idempotencyKey)->first()) {
                return $existing;
            }

            $balanceBefore = (int) $locked->balance_cached;
            $balanceAfter = $balanceBefore + $signedAmount;

            if ($balanceAfter < 0) {
                throw new InsufficientTokens($balanceBefore, abs($signedAmount));
            }

            $transaction = TokenTransaction::create([
                'token_account_id' => $locked->getKey(),
                'type' => $type,
                'amount' => $signedAmount,
                'balance_after' => $balanceAfter,
                'reference_type' => $reference ? $reference::class : null,
                'reference_id' => $reference?->getKey(),
                'idempotency_key' => $idempotencyKey,
                'metadata' => $metadata ?: null,
                'created_by' => $actor?->id,
            ]);

            $locked->forceFill(['balance_cached' => $balanceAfter])->save();
            $account->setAttribute('balance_cached', $balanceAfter);

            return $transaction;
        });
    }
}
