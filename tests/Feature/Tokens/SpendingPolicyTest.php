<?php

namespace Tests\Feature\Tokens;

use App\Services\Tokens\SpendingPolicy;

/**
 * Who pays tokens for an order, and who does not.
 *
 * Extends TokenTestCase rather than TokenHoldTestCase so the defaults are the
 * real ones — the hold test base switches spending on, which would make every
 * assertion here meaningless.
 *
 * The allowlist exists for one reason: the global switch is all-or-nothing, so
 * without it, testing holds against production data would mean blocking every
 * real guest from ordering.
 */
class SpendingPolicyTest extends TokenTestCase
{
    /* ─────────────────── the default: nobody pays ─────────────────── */

    public function test_nobody_spends_by_default(): void
    {
        $this->assertFalse(SpendingPolicy::appliesTo($this->makeUser()));
        $this->assertFalse(SpendingPolicy::enabledGlobally());
    }

    public function test_a_missing_user_never_spends(): void
    {
        $this->assertFalse(SpendingPolicy::appliesTo(null));
    }

    /* ─────────────────── the allowlist ─────────────────── */

    public function test_an_allowlisted_account_spends_while_everyone_else_does_not(): void
    {
        $tester = $this->makeUser(['email' => 'stripe-test@kialvo.com']);
        $customer = $this->makeUser(['email' => 'a.real.guest@example.com']);

        config(['tokens.spending_test_users' => ['stripe-test@kialvo.com']]);

        $this->assertTrue(SpendingPolicy::appliesTo($tester));
        $this->assertFalse(
            SpendingPolicy::appliesTo($customer),
            'a real customer must keep todays behaviour while the test account is exercised'
        );
    }

    /** Emails are compared case-insensitively — nobody types them consistently. */
    public function test_the_allowlist_ignores_case(): void
    {
        $user = $this->makeUser(['email' => 'Stripe-Test@Kialvo.com']);

        config(['tokens.spending_test_users' => ['stripe-test@kialvo.com']]);

        $this->assertTrue(SpendingPolicy::appliesTo($user));
    }

    public function test_an_empty_allowlist_lets_nobody_through(): void
    {
        config(['tokens.spending_test_users' => []]);

        $this->assertFalse(SpendingPolicy::appliesTo($this->makeUser()));
    }

    /** A near miss is a miss — no prefix or domain matching. */
    public function test_a_similar_email_is_not_allowlisted(): void
    {
        config(['tokens.spending_test_users' => ['stripe-test@kialvo.com']]);

        $this->assertFalse(SpendingPolicy::appliesTo(
            $this->makeUser(['email' => 'stripe-test@kialvo.com.attacker.net'])
        ));
        $this->assertFalse(SpendingPolicy::appliesTo(
            $this->makeUser(['email' => 'notstripe-test@kialvo.com'])
        ));
    }

    /* ─────────────────── the global switch ─────────────────── */

    public function test_the_global_switch_applies_to_everybody(): void
    {
        config(['tokens.spending_enabled' => true, 'tokens.spending_test_users' => []]);

        $this->assertTrue(SpendingPolicy::appliesTo($this->makeUser()));
        $this->assertTrue(SpendingPolicy::enabledGlobally());
    }

    /**
     * The allowlist only ever ADDS accounts. It must not be usable to carve
     * anyone out once the switch is on — a kill switch some accounts ignore
     * is not a kill switch.
     */
    public function test_the_allowlist_cannot_exclude_anyone_once_the_switch_is_on(): void
    {
        config([
            'tokens.spending_enabled' => true,
            'tokens.spending_test_users' => ['someone.else@example.com'],
        ]);

        $this->assertTrue(SpendingPolicy::appliesTo(
            $this->makeUser(['email' => 'not.on.the.list@example.com'])
        ));
    }

    /* ─────────────────── the config itself ─────────────────── */

    /** Shipping this must not enrol anybody by accident. */
    public function test_the_allowlist_ships_empty(): void
    {
        $this->assertSame(
            [],
            config('tokens.spending_test_users'),
            'TOKENS_SPENDING_TEST_USERS must default to empty — nobody is enrolled by deploying.'
        );
    }
}
