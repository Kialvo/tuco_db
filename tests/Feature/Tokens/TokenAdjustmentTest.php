<?php

namespace Tests\Feature\Tokens;

use App\Models\TokenTransaction;
use App\Services\Tokens\TeamMembership;
use App\Services\Tokens\TokenLedger;

/**
 * Manual adjustments: goodwill credit, and clawing back tokens granted in
 * error.
 *
 * The properties that matter are not "does it add tokens" but the guards
 * around it — a double-clicked form must book once, a reason is mandatory,
 * and no adjustment may drive a balance negative.
 */
class TokenAdjustmentTest extends TokenTestCase
{
    private function ledger(): TokenLedger
    {
        return app(TokenLedger::class);
    }

    public function test_a_goodwill_credit_adds_tokens_and_names_the_admin(): void
    {
        $customer = $this->makeUser();
        $admin = $this->makeUser(['role' => 'admin']);
        $account = $this->ledger()->accountFor($customer);

        $this->ledger()->adjust($account, 250, 'Goodwill for the delayed placement', 'adjustment:abc', $admin);

        $this->assertSame(250, $this->ledger()->balance($customer));

        $tx = TokenTransaction::where('type', TokenTransaction::TYPE_ADJUSTMENT)->first();
        $this->assertSame($admin->id, (int) $tx->created_by);
        $this->assertSame('Goodwill for the delayed placement', $tx->metadata['reason']);
    }

    public function test_a_negative_adjustment_removes_tokens(): void
    {
        $customer = $this->makeUser();
        $admin = $this->makeUser(['role' => 'admin']);
        $account = $this->ledger()->accountFor($customer);

        $this->ledger()->adjust($account, 500, 'Initial grant', 'adjustment:one', $admin);
        $this->ledger()->adjust($account, -200, 'Granted in error', 'adjustment:two', $admin);

        $this->assertSame(300, $this->ledger()->balance($customer));
    }

    /**
     * The reason this takes a caller-supplied key: there is no order or
     * purchase to derive a natural one from, so a double-clicked submit would
     * otherwise book twice.
     */
    public function test_the_same_form_submitted_twice_books_once(): void
    {
        $customer = $this->makeUser();
        $admin = $this->makeUser(['role' => 'admin']);
        $account = $this->ledger()->accountFor($customer);

        $this->ledger()->adjust($account, 250, 'Goodwill', 'adjustment:same-form', $admin);
        $this->ledger()->adjust($account->fresh(), 250, 'Goodwill', 'adjustment:same-form', $admin);

        $this->assertSame(250, $this->ledger()->balance($customer));
        $this->assertSame(1, TokenTransaction::where('type', TokenTransaction::TYPE_ADJUSTMENT)->count());
    }

    /** A row nobody can explain later is worse than no row. */
    public function test_an_adjustment_without_a_reason_is_refused(): void
    {
        $admin = $this->makeUser(['role' => 'admin']);
        $account = $this->ledger()->accountFor($this->makeUser());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/needs a reason/');

        $this->ledger()->adjust($account, 100, '   ', 'adjustment:x', $admin);
    }

    public function test_a_zero_adjustment_is_refused(): void
    {
        $admin = $this->makeUser(['role' => 'admin']);
        $account = $this->ledger()->accountFor($this->makeUser());

        $this->expectException(\InvalidArgumentException::class);

        $this->ledger()->adjust($account, 0, 'Does nothing', 'adjustment:zero', $admin);
    }

    /** The ledger's own guard: no adjustment may overdraw an account. */
    public function test_an_adjustment_cannot_drive_a_balance_negative(): void
    {
        $customer = $this->makeUser();
        $admin = $this->makeUser(['role' => 'admin']);
        $account = $this->ledger()->accountFor($customer);

        $this->ledger()->adjust($account, 100, 'Small grant', 'adjustment:a', $admin);

        $this->expectException(\App\Exceptions\InsufficientTokens::class);

        $this->ledger()->adjust($account->fresh(), -500, 'Too much', 'adjustment:b', $admin);
    }

    /** Adjustments land on the TEAM wallet, so a colleague sees them too. */
    public function test_an_adjustment_credits_the_whole_team(): void
    {
        $owner = $this->makeUser(['email' => 'boss@agency.com']);
        $colleague = $this->makeUser(['email' => 'junior@agency.com']);
        $admin = $this->makeUser(['role' => 'admin']);

        $teams = app(TeamMembership::class);
        $team = $teams->teamFor($owner);
        $teams->accept($teams->invite($team, $owner, 'junior@agency.com'), $colleague);

        $this->ledger()->adjust(
            $this->ledger()->accountFor($owner), 400, 'Goodwill', 'adjustment:team', $admin
        );

        $this->assertSame(400, $this->ledger()->balance($colleague));
    }
}
