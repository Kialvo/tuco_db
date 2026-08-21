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
