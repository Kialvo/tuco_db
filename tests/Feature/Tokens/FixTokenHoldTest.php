<?php

namespace Tests\Feature\Tokens;

use App\Models\OrderItem;
use App\Models\TokenTransaction;
use App\Services\Tokens\TokenLedger;

/**
 * The manual repair for a hold that settlement left open.
 *
 * Pairs with tokens:reconcile, which reports the problem and fixes nothing.
 * Everything routes through TokenLedger, so the same guards hold — this is a
 * convenient way to call it, not a way around it.
 */
class FixTokenHoldTest extends TokenHoldTestCase
{
    private function ledger(): TokenLedger
    {
        return app(TokenLedger::class);
    }

    /** @return array{0: \App\Models\User, 1: OrderItem} */
    private function heldItem(): array
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->ledger()->holdForItem($account, $item);

        return [$user, $item->fresh()];
    }

    public function test_capturing_a_stuck_hold_charges_for_it(): void
    {
        [$user, $item] = $this->heldItem();

        $this->artisan('tokens:fix-hold', ['item' => $item->id, '--capture' => true, '--force' => true])
            ->assertExitCode(0);

        $this->assertTrue($item->fresh()->isCaptured());
        $this->assertSame(473, $this->ledger()->balance($user));
        $this->assertSame(1, TokenTransaction::where('type', TokenTransaction::TYPE_SPEND)->count());
    }

    public function test_releasing_a_stuck_hold_gives_the_tokens_back(): void
    {
        [$user, $item] = $this->heldItem();

        $this->artisan('tokens:fix-hold', [
            'item' => $item->id,
            '--release' => OrderItem::RELEASE_ADMIN,
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertTrue($item->fresh()->isReleased());
        $this->assertSame(OrderItem::RELEASE_ADMIN, $item->fresh()->release_reason);
        $this->assertSame(1000, $this->ledger()->balance($user));
    }

    public function test_dry_run_changes_nothing(): void
    {
        [$user, $item] = $this->heldItem();

        $this->artisan('tokens:fix-hold', [
            'item' => $item->id,
            '--capture' => true,
            '--dry-run' => true,
            '--force' => true,
        ])->expectsOutputToContain('DRY RUN')->assertExitCode(0);

        $this->assertTrue($item->fresh()->isHeld());
        $this->assertSame(473, $this->ledger()->balance($user));
    }

    public function test_it_refuses_without_an_action(): void
    {
        [, $item] = $this->heldItem();

        $this->artisan('tokens:fix-hold', ['item' => $item->id])
            ->expectsOutputToContain('exactly one')
            ->assertExitCode(1);
    }

    public function test_it_refuses_both_actions_at_once(): void
    {
        [, $item] = $this->heldItem();

        $this->artisan('tokens:fix-hold', [
            'item' => $item->id,
            '--capture' => true,
            '--release' => 'admin',
            '--force' => true,
        ])->expectsOutputToContain('exactly one')->assertExitCode(1);
    }

    public function test_an_unknown_item_is_reported(): void
    {
        $this->artisan('tokens:fix-hold', ['item' => 999999, '--capture' => true, '--force' => true])
            ->expectsOutputToContain('does not exist')
            ->assertExitCode(1);
    }

    /** Nothing to fix is not an error — reconcile may already have been acted on. */
    public function test_an_item_with_no_hold_is_a_no_op(): void
    {
        $user = $this->makeUser();
        $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->artisan('tokens:fix-hold', ['item' => $item->id, '--capture' => true, '--force' => true])
            ->expectsOutputToContain('Nothing to do')
            ->assertExitCode(0);

        $this->assertSame(1000, $this->ledger()->balance($user));
    }

    /** The ledger's guards still apply — this is not a way around them. */
    public function test_it_cannot_release_something_already_captured(): void
    {
        [$user, $item] = $this->heldItem();

        $this->artisan('tokens:fix-hold', ['item' => $item->id, '--capture' => true, '--force' => true]);

        $this->artisan('tokens:fix-hold', [
            'item' => $item->id,
            '--release' => OrderItem::RELEASE_ADMIN,
            '--force' => true,
        ])->expectsOutputToContain('Nothing to do')->assertExitCode(0);

        $this->assertSame(473, $this->ledger()->balance($user), 'the customer must not be refunded twice');
    }
}
