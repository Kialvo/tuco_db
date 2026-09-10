<?php

namespace App\Services\Tokens;

use App\Models\MarketplaceTeam;
use App\Models\MarketplaceTeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Who belongs to which team, and who may change that.
 *
 * The rules, agreed 2026-09-09:
 *
 *   - Nobody joins by typing a company name. The first person to register owns
 *     a team of one; colleagues arrive only by invitation to their email.
 *   - The owner may invite, remove anyone, and hand ownership over, at any
 *     time, without our approval. We are told for visibility, not to gate it.
 *   - Everyone on the team can see the balance, and everyone can spend it.
 *     Stopping a junior from ordering is the agency's job, not ours.
 *
 * The one rule that is ours rather than theirs: an owner cannot remove
 * themselves. Ownership must transfer first, or a team ends up with a balance
 * and nobody entitled to manage it.
 */
class TeamMembership
{
    /** How long an invitation stays redeemable. */
    public const INVITATION_DAYS = 14;

    /**
     * The team this user spends from, creating a team of one if they have none.
     *
     * Every caller goes through here rather than reading the pivot directly,
     * so a user can never end up transacting without a team behind them.
     */
    public function teamFor(User $user): MarketplaceTeam
    {
        $team = MarketplaceTeam::whereHas('members', fn ($q) => $q->whereKey($user->id))->first();

        if ($team) {
            return $team;
        }

        return DB::transaction(function () use ($user) {
            // Re-check under the transaction: two concurrent requests for a
            // brand-new account would otherwise each create a team, and the
            // second would collide on the unique user_id anyway — better to
            // return the first than to surface a constraint violation.
            $existing = MarketplaceTeam::whereHas('members', fn ($q) => $q->whereKey($user->id))->first();

            if ($existing) {
                return $existing;
            }

            $team = MarketplaceTeam::create([
                'name' => $user->name ?: 'My account',
                'owner_user_id' => $user->id,
            ]);

            $team->members()->attach($user->id, ['joined_at' => now()]);

            return $team;
        });
    }

    /**
     * Invite an email address to a team.
     *
     * @throws \RuntimeException when the actor does not own the team
     */
    public function invite(MarketplaceTeam $team, User $actor, string $email): MarketplaceTeamInvitation
    {
        $this->assertOwner($team, $actor, 'invite people to');

        $email = mb_strtolower(trim($email));

        // Re-inviting is not an error — people lose emails. Refresh the
        // existing invitation rather than accumulating duplicates for one
        // address, so revoking later means revoking one row.
        $invitation = $team->invitations()
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->first();

        if ($invitation) {
            $invitation->forceFill([
                'token' => MarketplaceTeamInvitation::freshToken(),
                'expires_at' => now()->addDays(self::INVITATION_DAYS),
                'invited_by' => $actor->id,
            ])->save();

            return $invitation;
        }

        $invitation = $team->invitations()->create([
            'email' => $email,
            'token' => MarketplaceTeamInvitation::freshToken(),
            'invited_by' => $actor->id,
            'expires_at' => now()->addDays(self::INVITATION_DAYS),
        ]);

        // Visibility, not approval: we do not gate who joins a customer's
        // team, but we should be able to see it happening.
        Log::info("[teams] {$actor->email} invited {$email} to team {$team->id} ({$team->name})");

        return $invitation;
    }

    /**
     * Redeem an invitation.
     *
     * Refuses unless the invitation is still open AND the account accepting it
     * is the address it was sent to — a forwarded email must not let a third
     * party into somebody else's wallet.
     *
     * @throws \RuntimeException
     */
    public function accept(MarketplaceTeamInvitation $invitation, User $user): MarketplaceTeam
    {
        if (! $invitation->isPending()) {
            throw new \RuntimeException('This invitation has expired or has already been used.');
        }

        if (! $invitation->isFor($user)) {
            throw new \RuntimeException('This invitation was sent to a different email address.');
        }

        return DB::transaction(function () use ($invitation, $user) {
            $team = $invitation->team;

            // Leaving the old team is implicit: one team per person, enforced
            // by the unique index. Detaching first keeps that from surfacing
            // as a constraint violation.
            $this->detachFromCurrentTeam($user);

            $team->members()->syncWithoutDetaching([$user->id => ['joined_at' => now()]]);

            $invitation->forceFill(['accepted_at' => now()])->save();

            Log::info("[teams] {$user->email} joined team {$team->id} ({$team->name})");

            return $team;
        });
    }

    /**
     * Remove someone from a team. The owner may do this at any time.
     *
     * Their orders and the ledger are untouched — those are the team's history,
     * not the person's, and rewriting them would be falsifying an audit trail.
     * What they lose is the ability to spend.
     *
     * @throws \RuntimeException
     */
    public function remove(MarketplaceTeam $team, User $actor, User $member): void
    {
        $this->assertOwner($team, $actor, 'remove people from');

        if ($team->isOwnedBy($member)) {
            throw new \RuntimeException(
                'The owner cannot be removed. Transfer ownership to someone else first, '
                .'or the team is left with a balance and nobody able to manage it.'
            );
        }

        if (! $team->hasMember($member)) {
            return;   // already gone — a no-op, not an error
        }

        $team->members()->detach($member->id);

        // Any open invitation for them is revoked too, or removing someone
        // would leave a live link that puts them straight back.
        $team->invitations()
            ->where('email', mb_strtolower((string) $member->email))
            ->whereNull('accepted_at')
            ->delete();

        Log::info("[teams] {$actor->email} removed {$member->email} from team {$team->id}");
    }

    /**
     * Hand the team over. The new owner must already be a member — ownership
     * of a balance should never land on somebody who has not joined.
     *
     * @throws \RuntimeException
     */
    public function transferOwnership(MarketplaceTeam $team, User $actor, User $newOwner): void
    {
        $this->assertOwner($team, $actor, 'transfer ownership of');

        if (! $team->hasMember($newOwner)) {
            throw new \RuntimeException('Ownership can only be given to someone already on the team.');
        }

        $team->forceFill(['owner_user_id' => $newOwner->id])->save();

        Log::info("[teams] team {$team->id} ownership: {$actor->email} -> {$newOwner->email}");
    }

    /** Revoke an invitation that has not been used. */
    public function revokeInvitation(MarketplaceTeam $team, User $actor, MarketplaceTeamInvitation $invitation): void
    {
        $this->assertOwner($team, $actor, 'manage invitations for');

        if ((int) $invitation->team_id !== (int) $team->id) {
            throw new \RuntimeException('That invitation belongs to another team.');
        }

        if ($invitation->accepted_at !== null) {
            throw new \RuntimeException('That invitation has already been accepted. Remove the member instead.');
        }

        $invitation->delete();
    }

    private function detachFromCurrentTeam(User $user): void
    {
        DB::table('marketplace_team_members')->where('user_id', $user->id)->delete();
    }

    private function assertOwner(MarketplaceTeam $team, User $actor, string $action): void
    {
        if (! $team->isOwnedBy($actor)) {
            throw new \RuntimeException("Only the team owner can {$action} this team.");
        }
    }
}
