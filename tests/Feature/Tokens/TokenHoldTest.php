<?php

namespace Tests\Feature\Tokens;

use App\Exceptions\InsufficientTokens;
use App\Models\OrderItem;
use App\Models\TokenTransaction;
use App\Services\Tokens\TokenLedger;

/**
 * Holding, releasing and capturing tokens per ordered site.
 *
 * The rules being protected, all agreed 2026-09-09:
 *
 *  - Tokens are HELD when the order is placed, not spent.
 *  - They become revenue only when the article is published.
 *  - A publisher failing releases that site's tokens and NOTHING else.
 *  - A client going quiet does not release anything.
 */
class TokenHoldTest extends TokenHoldTestCase
{
    private function ledger(): TokenLedger
    {
        return app(TokenLedger::class);
    }

    /* ─────────────────────────── holding ─────────────────────────── */

    /** A hold is a real debit: held tokens must not be spendable twice. */
    public function test_holding_removes_the_tokens_from_the_spendable_balance(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->ledger()->holdForItem($account, $item);

        $this->assertSame(473, $this->ledger()->balance($user));
        $this->assertSame(527, $this->ledger()->heldTotal($account->fresh()));
    }

    public function test_holding_stamps_the_item_with_what_was_committed(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->ledger()->holdForItem($account, $item);

        $item->refresh();
        $this->assertSame(527, $item->tokens_held);
        $this->assertNotNull($item->held_at);
        $this->assertTrue($item->isHeld());
    }

    /** An order that cannot be paid for must not be placeable. */
    public function test_holding_more_than_the_balance_is_refused(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 500);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->expectException(InsufficientTokens::class);

        try {
            $this->ledger()->holdForItem($account, $item);
        } finally {
            // Nothing may have moved, and nothing may have been stamped.
            $this->assertSame(500, $this->ledger()->balance($user));
            $this->assertNull($item->fresh()->held_at);
        }
    }

    /** A double-clicked Submit must commit the tokens once. */
    public function test_holding_twice_commits_only_once(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $first = $this->ledger()->holdForItem($account, $item);
        $again = $this->ledger()->holdForItem($account->fresh(), $item->fresh());

        $this->assertSame($first->id, $again->id);
        $this->assertSame(473, $this->ledger()->balance($user));
        $this->assertSame(1, TokenTransaction::where('type', TokenTransaction::TYPE_HOLD)->count());
    }

    /* ─────────────────────────── releasing ─────────────────────────── */

    public function test_releasing_gives_the_tokens_back(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->ledger()->holdForItem($account, $item);
        $this->ledger()->releaseHold($account->fresh(), $item->fresh(), OrderItem::RELEASE_PUBLISHER_REFUSED);

        $this->assertSame(1000, $this->ledger()->balance($user));
        $this->assertSame(0, $this->ledger()->heldTotal($account->fresh()));

        $item->refresh();
        $this->assertTrue($item->isReleased());
        $this->assertSame(OrderItem::RELEASE_PUBLISHER_REFUSED, $item->release_reason);
    }

    /**
     * THE rule from 2026-09-09: one publisher failing must not collapse the
     * whole order. The other four sites keep running, and keep their tokens.
     */
    public function test_releasing_one_site_leaves_the_rest_of_the_order_held(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 2000);
        $order = $this->makeOrder($user, [100, 200, 300]);

        foreach ($order->items as $item) {
            $this->ledger()->holdForItem($account->fresh(), $item);
        }

        $this->assertSame(1400, $this->ledger()->balance($user));

        $dropped = $order->items->firstWhere('unit_price', 200.00);
        $this->ledger()->releaseHold($account->fresh(), $dropped->fresh(), OrderItem::RELEASE_PUBLISHER_DISAPPEARED);

        // Only the 200 came back; 100 + 300 are still committed.
        $this->assertSame(1600, $this->ledger()->balance($user));
        $this->assertSame(400, $this->ledger()->heldTotal($account->fresh()));
    }

    public function test_releasing_twice_returns_the_tokens_once(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->ledger()->holdForItem($account, $item);
        $this->ledger()->releaseHold($account->fresh(), $item->fresh(), OrderItem::RELEASE_CANCELLED);
        $second = $this->ledger()->releaseHold($account->fresh(), $item->fresh(), OrderItem::RELEASE_CANCELLED);

        $this->assertNull($second);
        $this->assertSame(1000, $this->ledger()->balance($user));
    }

    public function test_releasing_something_never_held_does_nothing(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->assertNull($this->ledger()->releaseHold($account, $item, OrderItem::RELEASE_ADMIN));
        $this->assertSame(1000, $this->ledger()->balance($user));
    }

    /* ─────────────────────────── capturing ─────────────────────────── */

    /**
     * Publication is when the money is earned. The spendable balance does not
     * move — it already dropped at hold time — but the ledger now records the
     * moment the commitment became revenue.
     */
    public function test_capturing_does_not_move_the_balance_but_records_the_spend(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->ledger()->holdForItem($account, $item);
        $balanceWhileHeld = $this->ledger()->balance($user);

        $this->ledger()->captureHold($account->fresh(), $item->fresh());

        $this->assertSame($balanceWhileHeld, $this->ledger()->balance($user));
        $this->assertSame(473, $this->ledger()->balance($user));

        $spend = TokenTransaction::where('type', TokenTransaction::TYPE_SPEND)->first();
        $this->assertNotNull($spend, 'capture must write a spend row');
        $this->assertSame(-527, (int) $spend->amount);
    }

    /** Once captured the tokens are no longer held — they are gone, earned. */
    public function test_capturing_clears_the_held_total(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->ledger()->holdForItem($account, $item);
        $this->ledger()->captureHold($account->fresh(), $item->fresh());

        $this->assertSame(0, $this->ledger()->heldTotal($account->fresh()));
        $this->assertTrue($item->fresh()->isCaptured());
    }

    /** A republished/replayed publication event must not charge twice. */
    public function test_capturing_twice_charges_once(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->ledger()->holdForItem($account, $item);
        $this->ledger()->captureHold($account->fresh(), $item->fresh());
        $second = $this->ledger()->captureHold($account->fresh(), $item->fresh());

        $this->assertNull($second);
        $this->assertSame(473, $this->ledger()->balance($user));
        $this->assertSame(1, TokenTransaction::where('type', TokenTransaction::TYPE_SPEND)->count());
    }

    /* ────────────────────── illegal transitions ────────────────────── */

    /** Releasing a published placement would be giving away the work. */
    public function test_releasing_a_captured_item_is_refused(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->ledger()->holdForItem($account, $item);
        $this->ledger()->captureHold($account->fresh(), $item->fresh());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/already captured/');

        $this->ledger()->releaseHold($account->fresh(), $item->fresh(), OrderItem::RELEASE_ADMIN);
    }

    /** Charging for a site whose tokens went back is charging twice. */
    public function test_capturing_a_released_item_is_refused(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->ledger()->holdForItem($account, $item);
        $this->ledger()->releaseHold($account->fresh(), $item->fresh(), OrderItem::RELEASE_PUBLISHER_REFUSED);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/released/');

        $this->ledger()->captureHold($account->fresh(), $item->fresh());
    }

    public function test_capturing_something_never_held_is_refused(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/nothing was ever held/');

        $this->ledger()->captureHold($account, $item);
    }

    /* ─────────────────────── whole-order holds ─────────────────────── */

    /** Placing an order commits every site on it. */
    public function test_holding_an_order_commits_every_site(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 2000);
        $order = $this->makeOrder($user, [100, 200, 300]);

        $held = $this->ledger()->holdForOrder($account, $order, $user);

        $this->assertSame(600, $held);
        $this->assertSame(1400, $this->ledger()->balance($user));
        $this->assertSame(600, $this->ledger()->heldTotal($account->fresh()));

        foreach ($order->fresh('items')->items as $item) {
            $this->assertTrue($item->isHeld(), "item {$item->id} should be held");
        }
    }

    /**
     * All or nothing. A partially-held order — some sites committed, some not,
     * status already 'submitted' — is worse than a rejected one, because
     * nothing downstream would ever know.
     */
    public function test_an_unaffordable_order_holds_nothing_at_all(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 250);
        $order = $this->makeOrder($user, [100, 200, 300]);

        try {
            $this->ledger()->holdForOrder($account, $order, $user);
            $this->fail('holdForOrder accepted an order the balance could not cover');
        } catch (InsufficientTokens $e) {
            // The shortfall quoted is for the WHOLE order, not the first site
            // that happened to run out.
            $this->assertSame(600, $e->required);
            $this->assertSame(250, $e->balance);
            $this->assertSame(350, $e->shortfall());
        }

        $this->assertSame(250, $this->ledger()->balance($user));
        $this->assertSame(0, $this->ledger()->heldTotal($account->fresh()));
        $this->assertSame(0, TokenTransaction::where('type', TokenTransaction::TYPE_HOLD)->count());
    }

    /** Exactly enough is enough — an off-by-one here refuses a valid order. */
    public function test_an_order_costing_the_entire_balance_is_allowed(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 600);
        $order = $this->makeOrder($user, [100, 200, 300]);

        $this->ledger()->holdForOrder($account, $order, $user);

        $this->assertSame(0, $this->ledger()->balance($user));
        $this->assertSame(600, $this->ledger()->heldTotal($account->fresh()));
    }

    /** A second order cannot be paid for with tokens the first one is holding. */
    public function test_held_tokens_cannot_fund_a_second_order(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 600);

        $this->ledger()->holdForOrder($account, $this->makeOrder($user, [600]), $user);

        $this->expectException(InsufficientTokens::class);

        $this->ledger()->holdForOrder($account->fresh(), $this->makeOrder($user, [100]), $user);
    }

    /* ─────────────────────── the ledger tells the story ─────────────────────── */

    /**
     * A full happy path leaves an auditable trail: what was committed, when it
     * was released, and when it became revenue.
     */
    public function test_the_ledger_explains_the_whole_lifecycle(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->ledger()->holdForItem($account, $item);
        $this->ledger()->captureHold($account->fresh(), $item->fresh());

        $types = TokenTransaction::orderBy('id')->pluck('type')->all();

        $this->assertSame([
            TokenTransaction::TYPE_ADJUSTMENT,   // the test funding
            TokenTransaction::TYPE_HOLD,
            TokenTransaction::TYPE_RELEASE,      // capture releases...
            TokenTransaction::TYPE_SPEND,        // ...then spends
        ], $types);

        // And the cached balance still agrees with the sum of every row.
        $this->assertSame(
            (int) TokenTransaction::sum('amount'),
            (int) $account->fresh()->balance_cached,
        );
    }

    /** A client going silent releases nothing — that was the explicit decision. */
    public function test_nothing_releases_a_hold_by_itself(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->ledger()->holdForItem($account, $item);

        $this->travel(30)->days();

        $this->assertSame(473, $this->ledger()->balance($user));
        $this->assertTrue($item->fresh()->isHeld());
    }
}
