<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A user's prepaid credit account.
 *
 * balance_cached is a cache of the ledger, never the source of truth. Read it
 * for display and for the balance check inside a locked transaction; trust
 * derivedBalance() when the two are ever compared.
 */
class TokenAccount extends Model
{
    protected $fillable = ['user_id', 'balance_cached'];

    protected $casts = ['balance_cached' => 'integer'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(TokenTransaction::class);
    }

    /** The authoritative balance, recomputed from the ledger. */
    public function derivedBalance(): int
    {
        return (int) $this->transactions()->sum('amount');
    }

    /** True when the cached balance has drifted from the ledger. */
    public function hasDrifted(): bool
    {
        return $this->derivedBalance() !== (int) $this->balance_cached;
    }
}
