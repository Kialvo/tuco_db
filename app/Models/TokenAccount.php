<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A team's prepaid credit account.
 *
 * balance_cached is a cache of the ledger, never the source of truth. Read it
 * for display and for the balance check inside a locked transaction; trust
 * derivedBalance() when the two are ever compared.
 */
class TokenAccount extends Model
{
    protected $fillable = ['user_id', 'team_id', 'balance_cached'];

    protected $casts = ['balance_cached' => 'integer'];

    /**
     * Who the wallet belongs to. The balance is the TEAM's — several people
     * from one agency spend it — so this, not user_id, is what identifies it.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(MarketplaceTeam::class, 'team_id');
    }

    /**
     * The account this wallet was originally opened under. Kept as history;
     * nothing reads it to decide who may spend.
     */
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
