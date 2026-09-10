<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One immutable ledger entry.
 *
 * UPDATED_AT is disabled deliberately: these rows are never modified, so a
 * changing timestamp would be a lie. Corrections are new compensating rows.
 */
class TokenTransaction extends Model
{
    public const UPDATED_AT = null;

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_SPEND = 'spend';

    public const TYPE_REFUND = 'refund';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_BONUS = 'bonus';

    public const TYPE_EXPIRY = 'expiry';

    /**
     * Tokens committed to an ordered site but not yet earned.
     *
     * A hold is a real DEBIT, so the balance a customer sees is always what
     * they can actually spend — committed tokens cannot be spent twice. It is
     * not revenue: it reverses in full if the site falls through.
     */
    public const TYPE_HOLD = 'hold';

    /** A hold given back — the publisher refused, vanished, or we cancelled. */
    public const TYPE_RELEASE = 'release';

    protected $fillable = [
        'token_account_id', 'type', 'amount', 'balance_after',
        'reference_type', 'reference_id', 'idempotency_key', 'metadata', 'created_by',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_after' => 'integer',
        'metadata' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(TokenAccount::class, 'token_account_id');
    }

    public function isCredit(): bool
    {
        return $this->amount > 0;
    }
}
