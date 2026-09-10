<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A group of people spending one balance.
 *
 * Everyone has one, even alone: a team of one on registration, which grows if
 * they invite colleagues. That uniformity is the point — a wallet always
 * belongs to a team, so there is no second code path for solo customers and no
 * migration to run the day one of them hires somebody.
 *
 * NOT the same thing as `companies`, which is shared with the Menford CRM and
 * describes a CRM client. This is who may spend a marketplace balance.
 */
class MarketplaceTeam extends Model
{
    protected $fillable = ['name', 'owner_user_id'];

    /** The wallet. Everything financial hangs off the team, never the user. */
    public function tokenAccount(): HasOne
    {
        return $this->hasOne(TokenAccount::class, 'team_id');
    }

    /** The only account that may invite, remove, or hand over ownership. */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'marketplace_team_members', 'team_id', 'user_id')
            ->withPivot('joined_at')
            ->withTimestamps();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(MarketplaceTeamInvitation::class, 'team_id');
    }

    public function pendingInvitations(): HasMany
    {
        return $this->invitations()->whereNull('accepted_at')->where('expires_at', '>', now());
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && (int) $this->owner_user_id === (int) $user->id;
    }

    public function hasMember(?User $user): bool
    {
        return $user !== null && $this->members()->whereKey($user->id)->exists();
    }

    /** Alone until somebody accepts an invitation. */
    public function isSolo(): bool
    {
        return $this->members()->count() <= 1;
    }
}
