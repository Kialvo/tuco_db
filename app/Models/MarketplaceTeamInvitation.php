<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * An owner's invitation for one email address to join their team.
 *
 * The token is the security of the whole feature. Without it, "joining a team"
 * would come down to knowing a company's name, and anyone could type "Acme
 * Agency" at sign-up and spend Acme's balance. Holding this token is proof the
 * owner sent it to that address.
 */
class MarketplaceTeamInvitation extends Model
{
    protected $fillable = ['team_id', 'email', 'token', 'invited_by', 'expires_at', 'accepted_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    /** Long enough that guessing is not a strategy. */
    public static function freshToken(): string
    {
        return Str::random(64);
    }

    /** Addresses are stored and compared lower-cased; nobody types them consistently. */
    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['email'] = $value === null ? null : mb_strtolower(trim($value));
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(MarketplaceTeam::class, 'team_id');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isPast();
    }

    /**
     * Only the invited address may redeem it.
     *
     * A forwarded invitation must not let a third party into somebody's
     * wallet, which is exactly what would happen if the token alone were
     * enough.
     */
    public function isFor(?User $user): bool
    {
        return $user !== null
            && $user->email !== null
            && mb_strtolower($user->email) === $this->email;
    }
}
