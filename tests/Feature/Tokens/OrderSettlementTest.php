<?php

namespace Tests\Feature\Tokens;

use App\Models\OrderItem;
use App\Models\TokenTransaction;
use App\Services\Tokens\OrderSettlement;
use App\Services\Tokens\TokenLedger;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * A publication's outcome turned into money.
 *
 * Published captures. Publisher refused or disappeared releases. Everything
 * else — including a client going quiet, which was the explicitly decided
 * case — moves nothing at all.
 */
class OrderSettlementTest extends TokenHoldTestCase
{
    private function ledger(): TokenLedger
    {
        return app(TokenLedger::class);
    }

    private function settlement(): OrderSettlement
    {
        return app(OrderSettlement::class);
    }

    /** Order one site, hold its tokens, and return [user, account, item]. */
    private function heldOrder(int $price = 527, int $funds = 1000): array
    {
        $user = $this->makeUser();
        $account = $this->fund($user, $funds);
        $item = $this->makeOrder($user, [$price])->items->first();

        $this->ledger()->holdForItem($account, $item);

        return [$user, $account->fresh(), $item->fresh()];
    }

    /* ─────────────────────────── capture ─────────────────────────── */

    /** Publication is the only thing that earns the tokens. */
    public function test_publishing_captures_the_hold(): void
    {
        [$user, , $item] = $this->heldOrder();
        $publication = $this->makePublication($item, 'article_published');

        $this->settlement()->settle($publication);

        $item->refresh();
        $this->assertTrue($item->isCaptured());
        $this->assertSame(473, $this->ledger()->balance($user));
        $this->assertSame(1, TokenTransaction::where('type', TokenTransaction::TYPE_SPEND)->count());
    }

    /** Martina re-saving a published publication must not charge twice. */
    public function test_settling_a_published_item_twice_charges_once(): void
    {
        [$user, , $item] = $this->heldOrder();
        $publication = $this->makePublication($item, 'article_published');

        $this->settlement()->settle($publication);
        $this->settlement()->settle($publication);
        $this->settlement()->settle($publication);

        $this->assertSame(473, $this->ledger()->balance($user));
        $this->assertSame(1, TokenTransaction::where('type', TokenTransaction::TYPE_SPEND)->count());
    }

    /* ─────────────────────────── release ─────────────────────────── */

    /** We never absorb a price increase — the site is dropped and refunded. */
    public function test_a_refusing_publisher_releases_the_hold(): void
    {
        [$user, $account, $item] = $this->heldOrder();
        $publication = $this->makePublication($item, 'publisher_refused');

        $this->settlement()->settle($publication);

        $item->refresh();
        $this->assertTrue($item->isReleased());
        $this->assertSame(OrderItem::RELEASE_PUBLISHER_REFUSED, $item->release_reason);
        $this->assertSame(1000, $this->ledger()->balance($user));
        $this->assertSame(0, $this->ledger()->heldTotal($account->fresh()));
    }

    public function test_a_vanished_publisher_releases_the_hold(): void
    {
        [$user, , $item] = $this->heldOrder();
        $publication = $this->makePublication($item, 'publisher_disappeared');

        $this->settlement()->settle($publication);

        $this->assertSame(1000, $this->ledger()->balance($user));
        $this->assertSame(OrderItem::RELEASE_PUBLISHER_DISAPPEARED, $item->fresh()->release_reason);
    }

    public function test_releasing_twice_returns_the_tokens_once(): void
    {
        [$user, , $item] = $this->heldOrder();
        $publication = $this->makePublication($item, 'publisher_refused');

        $this->settlement()->settle($publication);
        $this->settlement()->settle($publication);

        $this->assertSame(1000, $this->ledger()->balance($user));
        $this->assertSame(1, TokenTransaction::where('type', TokenTransaction::TYPE_RELEASE)->count());
    }

    /* ─────────────────── statuses that must move nothing ─────────────────── */

    public static function inertStatuses(): array
    {
        return [
            'waiting for the publisher' => ['waiting_blog_price_confirmation'],
            'waiting for the copywriter' => ['waiting_copywriter'],
            'waiting for the client to approve the article' => ['waiting_client_article_approval'],
            'waiting for the blog to publish' => ['waiting_blog_publication'],
            'accepted' => ['accepted'],
        ];
    }

    /**
     * The decided rule: nothing but publication or publisher failure settles a
     * hold. A client sitting on an article changes nothing financially.
     */
    #[DataProvider('inertStatuses')]
    public function test_an_in_progress_status_settles_nothing(string $status): void
    {
        [$user, $account, $item] = $this->heldOrder();
        $publication = $this->makePublication($item, $status);

        $this->settlement()->settle($publication);

        $this->assertSame(473, $this->ledger()->balance($user));
        $this->assertSame(527, $this->ledger()->heldTotal($account->fresh()));
        $this->assertTrue($item->fresh()->isHeld());
    }

    /* ─────────────────── things it must not touch ─────────────────── */

    /**
     * CRM and Monday-imported publications have no order behind them. A bulk
     * status change there must never move a customer's money.
     */
    public function test_a_publication_with_no_order_behind_it_is_ignored(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);

        // A publication that no order item points at.
        $orphanId = \Illuminate\Support\Facades\DB::table('storage')->insertGetId([
            'status' => 'article_published',
            'publisher_domain' => 'crm-only.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->settlement()->settle(\App\Models\Storage::find($orphanId));

        $this->assertSame(1000, $this->ledger()->balance($user));
        $this->assertSame(0, TokenTransaction::whereIn('type', [
            TokenTransaction::TYPE_SPEND,
            TokenTransaction::TYPE_RELEASE,
        ])->count());
    }

    /** An item that was never held has nothing to settle. */
    public function test_an_item_with_no_hold_is_ignored(): void
    {
        $user = $this->makeUser();
        $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $publication = $this->makePublication($item, 'article_published');

        $this->settlement()->settle($publication);

        $this->assertSame(1000, $this->ledger()->balance($user));
        $this->assertFalse($item->fresh()->isCaptured());
    }

    /**
     * A publisher failing after publication is nonsense, but Martina can pick
     * the wrong status. It must not hand back tokens for live work — the
     * ledger refuses, and settlement swallows and logs rather than breaking
     * her save.
     */
    public function test_a_late_failure_after_publication_does_not_refund(): void
    {
        [$user, , $item] = $this->heldOrder();

        $publication = $this->makePublication($item, 'article_published');
        $this->settlement()->settle($publication);

        $publication->forceFill(['status' => 'publisher_refused']);
        $this->settlement()->settle($publication);   // must not throw

        $this->assertSame(473, $this->ledger()->balance($user));
        $this->assertTrue($item->fresh()->isCaptured());
        $this->assertFalse($item->fresh()->isReleased());
    }

    /** Only the failing site settles; the rest of the order stays committed. */
    public function test_one_site_failing_leaves_the_others_held(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 2000);
        $order = $this->makeOrder($user, [100, 200, 300]);

        $this->ledger()->holdForOrder($account, $order, $user);
        $this->assertSame(1400, $this->ledger()->balance($user));

        $dropped = $order->fresh('items')->items->firstWhere('unit_price', 200.00);
        $this->settlement()->settle($this->makePublication($dropped, 'publisher_disappeared'));

        $this->assertSame(1600, $this->ledger()->balance($user));
        $this->assertSame(400, $this->ledger()->heldTotal($account->fresh()));
    }
}
