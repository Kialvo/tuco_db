<?php

namespace Tests\Feature\Tokens;

use App\Models\MarketplaceTeam;
use App\Models\TokenTransaction;
use App\Services\Tokens\TeamMembership;
use App\Services\Tokens\TokenLedger;

/**
 * Shared wallets: who is on a team, who may change that, and — the part that
 * actually matters — whose money they can spend.
 *
 * The rule that makes this safe: nobody joins by typing a company name. If
 * they could, anyone could sign up as "Acme Agency" and spend Acme's balance.
 */
class TeamMembershipTest extends TokenTestCase
{
    private function teams(): TeamMembership
    {
        return app(TeamMembership::class);
    }

    private function ledger(): TokenLedger
    {
        return app(TokenLedger::class);
    }

    /* ─────────────────── everyone has a team ─────────────────── */

    public function test_a_new_user_gets_a_team_of_one_that_they_own(): void
    {
        $user = $this->makeUser();

        $team = $this->teams()->teamFor($user);

        $this->assertTrue($team->isOwnedBy($user));
        $this->assertTrue($team->hasMember($user));
        $this->assertTrue($team->isSolo());
    }

    public function test_asking_twice_returns_the_same_team(): void
    {
        $user = $this->makeUser();

        $this->assertSame(
            $this->teams()->teamFor($user)->id,
            $this->teams()->teamFor($user)->id
        );
        $this->assertSame(1, MarketplaceTeam::count());
    }

    /** The wallet hangs off the team, so it is the same wallet either way. */
    public function test_the_wallet_belongs_to_the_team(): void
    {
        $user = $this->makeUser();
        $account = $this->ledger()->accountFor($user);

        $this->assertSame($this->teams()->teamFor($user)->id, (int) $account->team_id);
    }

    /* ─────────────────── invitations ─────────────────── */

    public function test_only_the_owner_can_invite(): void
    {
        $owner = $this->makeUser();
        $team = $this->teams()->teamFor($owner);
        $outsider = $this->makeUser();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/[Oo]nly the team owner/');

        $this->teams()->invite($team, $outsider, 'someone@example.com');
    }

    public function test_an_invited_colleague_joins_the_team(): void
    {
        $owner = $this->makeUser(['email' => 'boss@agency.com']);
        $team = $this->teams()->teamFor($owner);
        $colleague = $this->makeUser(['email' => 'junior@agency.com']);

        $invitation = $this->teams()->invite($team, $owner, 'junior@agency.com');
        $this->teams()->accept($invitation, $colleague);

        $this->assertTrue($team->fresh()->hasMember($colleague));
        $this->assertFalse($team->fresh()->isSolo());
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    /**
     * THE security property. A forwarded invitation must not let a stranger
     * into somebody else's wallet — holding the token is not enough.
     */
    public function test_an_invitation_cannot_be_redeemed_by_a_different_address(): void
    {
        $owner = $this->makeUser(['email' => 'boss@agency.com']);
        $team = $this->teams()->teamFor($owner);

        $invitation = $this->teams()->invite($team, $owner, 'junior@agency.com');
        $stranger = $this->makeUser(['email' => 'stranger@elsewhere.com']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/different email address/');

        $this->teams()->accept($invitation, $stranger);
    }

    public function test_an_expired_invitation_is_refused(): void
    {
        $owner = $this->makeUser();
        $team = $this->teams()->teamFor($owner);
        $colleague = $this->makeUser(['email' => 'late@agency.com']);

        $invitation = $this->teams()->invite($team, $owner, 'late@agency.com');
        $invitation->forceFill(['expires_at' => now()->subDay()])->save();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/expired|already been used/');

        $this->teams()->accept($invitation->fresh(), $colleague);
    }

    public function test_an_invitation_cannot_be_used_twice(): void
    {
        $owner = $this->makeUser();
        $team = $this->teams()->teamFor($owner);
        $colleague = $this->makeUser(['email' => 'junior@agency.com']);

        $invitation = $this->teams()->invite($team, $owner, 'junior@agency.com');
        $this->teams()->accept($invitation, $colleague);

        $this->expectException(\RuntimeException::class);

        $this->teams()->accept($invitation->fresh(), $colleague);
    }

    /** Re-inviting refreshes rather than piling up rows to revoke separately. */
    public function test_re_inviting_the_same_address_reuses_the_invitation(): void
    {
        $owner = $this->makeUser();
        $team = $this->teams()->teamFor($owner);

        $first = $this->teams()->invite($team, $owner, 'junior@agency.com');
        $second = $this->teams()->invite($team, $owner, 'JUNIOR@agency.com');

        $this->assertSame($first->id, $second->id);
        $this->assertNotSame($first->token, $second->fresh()->token, 'a re-invite should issue a fresh token');
        $this->assertSame(1, $team->invitations()->count());
    }

    /* ─────────────────── the shared balance ─────────────────── */

    /** The whole point: one balance, several people. */
    public function test_a_colleague_spends_from_the_same_balance(): void
    {
        $owner = $this->makeUser(['email' => 'boss@agency.com']);
        $team = $this->teams()->teamFor($owner);
        $colleague = $this->makeUser(['email' => 'junior@agency.com']);

        $this->teams()->accept($this->teams()->invite($team, $owner, 'junior@agency.com'), $colleague);

        $account = $this->ledger()->accountFor($owner);
        $this->ledger()->credit($account, 1000, TokenTransaction::TYPE_ADJUSTMENT, 'test:fund');

        $this->assertSame(1000, $this->ledger()->balance($colleague));
        $this->assertSame(
            $account->id,
            $this->ledger()->accountFor($colleague)->id,
            'both people must resolve to the one wallet'
        );
    }

    /** Two unrelated agencies must never see each other's money. */
    public function test_separate_teams_have_separate_balances(): void
    {
        $acme = $this->makeUser(['email' => 'boss@acme.com']);
        $other = $this->makeUser(['email' => 'boss@other.com']);

        $this->ledger()->credit(
            $this->ledger()->accountFor($acme), 5000, TokenTransaction::TYPE_ADJUSTMENT, 'test:acme'
        );

        $this->assertSame(5000, $this->ledger()->balance($acme));
        $this->assertSame(0, $this->ledger()->balance($other));
    }

    /**
     * The reason removal had to exist: with everyone able to spend, a departed
     * employee who kept access could drain their old agency's balance.
     */
    public function test_a_removed_member_can_no_longer_spend_the_teams_money(): void
    {
        $owner = $this->makeUser(['email' => 'boss@agency.com']);
        $team = $this->teams()->teamFor($owner);
        $leaver = $this->makeUser(['email' => 'leaver@agency.com']);

        $this->teams()->accept($this->teams()->invite($team, $owner, 'leaver@agency.com'), $leaver);

        $this->ledger()->credit(
            $this->ledger()->accountFor($owner), 5000, TokenTransaction::TYPE_ADJUSTMENT, 'test:fund'
        );
        $this->assertSame(5000, $this->ledger()->balance($leaver));

        $this->teams()->remove($team, $owner, $leaver);

        // They fall back to a team of their own, with nothing in it.
        $this->assertSame(0, $this->ledger()->balance($leaver));
        $this->assertSame(5000, $this->ledger()->balance($owner), 'the agency keeps its money');
    }

    /* ─────────────────── removal and ownership ─────────────────── */

    public function test_only_the_owner_can_remove_people(): void
    {
        $owner = $this->makeUser();
        $team = $this->teams()->teamFor($owner);
        $a = $this->makeUser(['email' => 'a@agency.com']);
        $b = $this->makeUser(['email' => 'b@agency.com']);

        $this->teams()->accept($this->teams()->invite($team, $owner, 'a@agency.com'), $a);
        $this->teams()->accept($this->teams()->invite($team, $owner, 'b@agency.com'), $b);

        $this->expectException(\RuntimeException::class);

        $this->teams()->remove($team, $a, $b);
    }

    /** Otherwise a team is left with a balance and nobody able to manage it. */
    public function test_the_owner_cannot_remove_themselves(): void
    {
        $owner = $this->makeUser();
        $team = $this->teams()->teamFor($owner);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/owner cannot be removed/');

        $this->teams()->remove($team, $owner, $owner);
    }

    /** Removing someone revokes their open invitation, or they walk back in. */
    public function test_removing_a_member_revokes_their_pending_invitation(): void
    {
        $owner = $this->makeUser();
        $team = $this->teams()->teamFor($owner);
        $member = $this->makeUser(['email' => 'member@agency.com']);

        $this->teams()->accept($this->teams()->invite($team, $owner, 'member@agency.com'), $member);
        $this->teams()->invite($team, $owner, 'member@agency.com');   // a stray re-invite

        $this->teams()->remove($team, $owner, $member);

        $this->assertSame(0, $team->pendingInvitations()->count());
    }

    public function test_ownership_can_be_handed_over(): void
    {
        $owner = $this->makeUser();
        $team = $this->teams()->teamFor($owner);
        $successor = $this->makeUser(['email' => 'successor@agency.com']);

        $this->teams()->accept($this->teams()->invite($team, $owner, 'successor@agency.com'), $successor);
        $this->teams()->transferOwnership($team, $owner, $successor);

        $this->assertTrue($team->fresh()->isOwnedBy($successor));

        // And the old owner is now an ordinary member who cannot invite.
        $this->expectException(\RuntimeException::class);
        $this->teams()->invite($team->fresh(), $owner, 'someone@else.com');
    }

    /** Ownership of a balance must never land on somebody who has not joined. */
    public function test_ownership_cannot_be_given_to_a_non_member(): void
    {
        $owner = $this->makeUser();
        $team = $this->teams()->teamFor($owner);
        $outsider = $this->makeUser();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/already on the team/');

        $this->teams()->transferOwnership($team, $owner, $outsider);
    }
}
