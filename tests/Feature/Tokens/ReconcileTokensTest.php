<?php

namespace Tests\Feature\Tokens;

use App\Models\TokenTransaction;
use App\Services\Tokens\OrderSettlement;
use App\Services\Tokens\TokenLedger;
use Illuminate\Support\Facades\DB;

/**
 * The safety net.
 *
 * OrderSettlement swallows its errors so a publication save can never fail on
 * bookkeeping. That is only defensible because this command notices afterwards.
 * These tests are therefore about one question: when settlement silently does
 * not happen, does anyone find out?
 */
class ReconcileTokensTest extends TokenHoldTestCase
{
    private function ledger(): TokenLedger
    {
        return app(TokenLedger::class);
    }

    public function test_a_healthy_ledger_reports_nothing_and_succeeds(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->ledger()->holdForItem($account, $item);

        $this->artisan('tokens:reconcile')
            ->expectsOutputToContain('Ledger and balances agree')
            ->assertExitCode(0);
    }

    public function test_a_fully_settled_order_reports_nothing(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->ledger()->holdForItem($account, $item);
        app(OrderSettlement::class)->settle($this->makePublication($item->fresh(), 'article_published'));

        $this->artisan('tokens:reconcile')->assertExitCode(0);
    }

    /**
     * THE case this command exists for: the article went live, but the tokens
     * were never captured. That is delivered work nobody was charged for, and
     * it is exactly what a swallowed settlement failure leaves behind.
     */
    public function test_a_published_article_with_an_open_hold_is_reported(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->ledger()->holdForItem($account, $item);

        // Publication reaches a terminal status without settlement running.
        $this->makePublication($item->fresh(), 'article_published');

        $this->artisan('tokens:reconcile')
            ->expectsOutputToContain('UNSETTLED')
            ->assertExitCode(1);
    }

    /** The mirror case: the site died, but the customer never got the tokens back. */
    public function test_a_dead_publication_with_an_open_hold_is_reported(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->ledger()->holdForItem($account, $item);
        $this->makePublication($item->fresh(), 'publisher_disappeared');

        $this->artisan('tokens:reconcile')
            ->expectsOutputToContain('UNSETTLED')
            ->assertExitCode(1);
    }

    /** An in-flight publication is not unsettled — it simply has not finished. */
    public function test_an_in_progress_publication_is_not_reported(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->ledger()->holdForItem($account, $item);
        $this->makePublication($item->fresh(), 'waiting_client_article_approval');

        $this->artisan('tokens:reconcile')->assertExitCode(0);
    }

    /**
     * balance_cached and the ledger are written in one transaction, so a
     * mismatch means something bypassed TokenLedger. It must be caught.
     */
    public function test_a_tampered_cached_balance_is_caught(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);

        // Simulate a direct write that skipped the ledger entirely.
        DB::table('token_accounts')->where('id', $account->id)->update(['balance_cached' => 9999]);

        $this->artisan('tokens:reconcile')
            ->expectsOutputToContain('DRIFT')
            ->assertExitCode(1);
    }

    /** A hold mark with no ledger row behind it is a partial write. */
    public function test_an_item_marked_held_with_no_ledger_row_is_caught(): void
    {
        $user = $this->makeUser();
        $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        // Marked as though held, but nothing was ever debited.
        $item->forceFill(['tokens_held' => 527, 'held_at' => now()])->save();

        $this->artisan('tokens:reconcile')
            ->expectsOutputToContain('ORPHANED')
            ->assertExitCode(1);
    }

    /** And the reverse: tokens debited, but the item never marked. */
    public function test_a_hold_row_with_no_marked_item_is_caught(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->ledger()->holdForItem($account, $item);

        // Wipe the mark, leaving the ledger row stranded.
        DB::table('order_items')->where('id', $item->id)
            ->update(['held_at' => null, 'tokens_held' => null]);

        $this->artisan('tokens:reconcile')
            ->expectsOutputToContain('ORPHANED')
            ->assertExitCode(1);
    }

    /**
     * Long holds are legitimate — revisions are unlimited and the approval
     * clock restarts each time — so a stale hold is reported without failing.
     */
    public function test_a_stale_hold_is_reported_but_does_not_fail(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->ledger()->holdForItem($account, $item);

        DB::table('order_items')->where('id', $item->id)
            ->update(['held_at' => now()->subDays(90)]);

        $this->artisan('tokens:reconcile', ['--stale-days' => 45])
            ->expectsOutputToContain('STALE')
            ->assertExitCode(0);
    }

    public function test_a_recent_hold_is_not_stale(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->ledger()->holdForItem($account, $item);

        $this->artisan('tokens:reconcile', ['--stale-days' => 45])
            ->doesntExpectOutputToContain('STALE')
            ->assertExitCode(0);
    }

    /** It reports; it never repairs. A wrong auto-fix to a ledger is worse. */
    public function test_it_changes_nothing(): void
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->ledger()->holdForItem($account, $item);
        $this->makePublication($item->fresh(), 'article_published');

        $rowsBefore = TokenTransaction::count();
        $balanceBefore = (int) $account->fresh()->balance_cached;

        $this->artisan('tokens:reconcile')->assertExitCode(1);

        $this->assertSame($rowsBefore, TokenTransaction::count());
        $this->assertSame($balanceBefore, (int) $account->fresh()->balance_cached);
        $this->assertTrue($item->fresh()->isHeld(), 'reconcile must not settle anything itself');
    }

    /** Quiet mode exists so a nightly schedule only speaks when it matters. */
    public function test_quiet_when_clean_prints_nothing(): void
    {
        $this->makeUser();

        $this->artisan('tokens:reconcile', ['--quiet-when-clean' => true])
            ->doesntExpectOutputToContain('agree')
            ->assertExitCode(0);
    }
}
