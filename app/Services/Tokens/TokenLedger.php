<?php

namespace App\Services\Tokens;

use App\Exceptions\InsufficientTokens;
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
