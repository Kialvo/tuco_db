<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenPurchase extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'user_id', 'token_account_id', 'package_key', 'tokens', 'bonus_tokens',
        'amount_minor', 'currency', 'gateway', 'gateway_session_id',
        'gateway_payment_id', 'status', 'billing_snapshot', 'paid_at',
    ];

    protected $casts = [
        'tokens' => 'integer',
        'bonus_tokens' => 'integer',
        'amount_minor' => 'integer',
        'billing_snapshot' => 'array',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(TokenAccount::class, 'token_account_id');
    }

    /** Everything the customer receives, discount included. */
    public function totalTokens(): int
    {
        return (int) $this->tokens + (int) $this->bonus_tokens;
    }
}
