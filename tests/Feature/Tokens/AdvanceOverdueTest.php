<?php

namespace Tests\Feature\Tokens;

use App\Models\OrderItem;
use App\Models\Storage;
use App\Services\Tokens\TokenLedger;
use Illuminate\Support\Facades\DB;

/**
 * The two deadlines that stop an order — and the money held against it —
 * waiting on somebody's silence forever.
 *
 *   publisher never confirms, 5 working days -> dropped, tokens released
 *   client never approves,    3 working days -> published anyway
 *
 * Both counted in working days, weekends excluded.
 */
class AdvanceOverdueTest extends TokenHoldTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The command is gated off by default in production; these tests are
        // about what it does once switched on.
        config(['linkbuilding.marketplace_deadlines.enabled' => true]);
    }

    private function ledger(): TokenLedger
    {
        return app(TokenLedger::class);
    }

    /** An order whose single site sits in `$status`, with its tokens held. */
    private function stuckAt(string $status, int $workingDaysAgo): array
    {
        $user = $this->makeUser();
        $account = $this->fund($user, 1000);
        $item = $this->makeOrder($user, [527])->items->first();

        $this->ledger()->holdForItem($account, $item);

        $publication = $this->makePublication($item->fresh(), $status);

        // Backdate the status event — that is where the clock is read from.
        // Calendar days deliberately overshoot the working-day count.
        DB::table('publication_status_events')
            ->where('storage_id', $publication->id)
            ->update(['created_at' => now()->subDays($workingDaysAgo * 2)]);

        return [$user, $account->fresh(), $item->fresh(), $publication];
    }

    /* ──────────────── publisher never confirmed ──────────────── */

    public function test_an_unconfirmed_price_expires_after_five_working_days(): void
    {
        [$user, $account, $item, $publication] = $this->stuckAt('waiting_blog_price_confirmation', 5);

        $this->artisan('marketplace:advance-overdue')->assertExitCode(0);

        $this->assertSame('publisher_disappeared', $publication->fresh()->status);

        // And the release happened on the back of that status change.
        $this->assertSame(1000, $this->ledger()->balance($user));
        $this->assertSame(0, $this->ledger()->heldTotal($account->fresh()));
        $this->assertSame(OrderItem::RELEASE_PUBLISHER_DISAPPEARED, $item->fresh()->release_reason);
    }

    public function test_a_publisher_still_within_the_deadline_is_left_alone(): void
    {
        [$user, , , $publication] = $this->stuckAt('waiting_blog_price_confirmation', 0);

        $this->artisan('marketplace:advance-overdue')->assertExitCode(0);

        $this->assertSame('waiting_blog_price_confirmation', $publication->fresh()->status);
        $this->assertSame(473, $this->ledger()->balance($user));
    }

    /* ──────────────── client never approved ──────────────── */

    /**
     * Silence is approval, so the order can finish and we can be paid — but
     * only on the run AFTER the client has been warned. The first pass sends
     * the reminder and deliberately does not publish.
     */
    public function test_an_unanswered_article_is_approved_after_being_reminded(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        [$user, $account, , $publication] = $this->stuckAt('waiting_client_article_approval', 3);

        // First run: reminded, not published.
        $this->artisan('marketplace:advance-overdue')->assertExitCode(0);

        $this->assertSame('waiting_client_article_approval', $publication->fresh()->status);
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ArticleApprovalReminderMail::class);

        // Second run: now it publishes.
        $this->artisan('marketplace:advance-overdue')->assertExitCode(0);

        $this->assertSame('waiting_blog_publication', $publication->fresh()->status);

        // Auto-approval moves the workflow on; it does NOT settle anything.
        // Only publication earns the tokens.
        $this->assertSame(473, $this->ledger()->balance($user));
        $this->assertSame(527, $this->ledger()->heldTotal($account->fresh()));
    }

    /**
     * The guarantee that makes auto-publishing defensible: an article can
     * never go live without the client having been emailed the date it would.
     */
    public function test_an_article_is_never_published_without_a_reminder(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        \Illuminate\Support\Facades\Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));

        [, , , $publication] = $this->stuckAt('waiting_client_article_approval', 30);

        // Run repeatedly with the mailer broken: it must never publish.
        $this->artisan('marketplace:advance-overdue')->assertExitCode(0);
        $this->artisan('marketplace:advance-overdue')->assertExitCode(0);
        $this->artisan('marketplace:advance-overdue')->assertExitCode(0);

        $this->assertSame(
            'waiting_client_article_approval',
            $publication->fresh()->status,
            'a failed reminder must never let the article be auto-published'
        );
    }

    /** A revised draft makes the previous reminder stale — the client is warned again. */
    public function test_a_new_draft_requires_a_fresh_reminder(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        [, , $item, $publication] = $this->stuckAt('waiting_client_article_approval', 3);

        $this->artisan('marketplace:advance-overdue');   // reminder for draft 1
        $this->assertNotNull($item->fresh()->approval_reminder_sent_at);

        // A corrected draft re-enters approval a minute after that reminder.
        $this->recordStatusEvent($publication->id, 'waiting_copywriter', now()->addMinute());
        $this->recordStatusEvent($publication->id, 'waiting_client_article_approval', now()->addMinutes(2));

        \Illuminate\Support\Facades\Mail::fake();
        $this->travel(5)->days();

        $this->artisan('marketplace:advance-overdue');

        // A fresh reminder is due: the one sent for draft 1 predates the moment
        // draft 2 entered approval, so it does not count for this cycle.
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ArticleApprovalReminderMail::class);
    }

    public function test_a_client_still_within_the_deadline_is_left_alone(): void
    {
        [, , , $publication] = $this->stuckAt('waiting_client_article_approval', 0);

        $this->artisan('marketplace:advance-overdue')->assertExitCode(0);

        $this->assertSame('waiting_client_article_approval', $publication->fresh()->status);
    }

    /**
     * The agreed rule: a corrected article restarts the three days. It works
     * because the publication leaves the status and comes back, writing a new
     * event — and the clock reads the LATEST one, not the first.
     */
    public function test_a_revised_article_restarts_the_clock(): void
    {
        [, , , $publication] = $this->stuckAt('waiting_client_article_approval', 3);

        // Client rejects; a new draft arrives and re-enters approval today.
        $this->recordStatusEvent($publication->id, 'waiting_copywriter', now()->subDay());
        $this->recordStatusEvent($publication->id, 'waiting_client_article_approval', now());

        $this->artisan('marketplace:advance-overdue')->assertExitCode(0);

        $this->assertSame(
            'waiting_client_article_approval',
            $publication->fresh()->status,
            'the new draft should have restarted the three working days'
        );
    }

    /* ──────────────── scope and safety ──────────────── */

    /**
     * Martina's own CRM work and the Monday-imported rows have no customer
     * waiting and must never be advanced by a machine.
     */
    public function test_a_publication_with_no_order_behind_it_is_never_touched(): void
    {
        $orphanId = DB::table('storage')->insertGetId([
            'status' => 'waiting_blog_price_confirmation',
            'publisher_domain' => 'crm-only.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->recordStatusEvent($orphanId, 'waiting_blog_price_confirmation', now()->subDays(60));

        $this->artisan('marketplace:advance-overdue')->assertExitCode(0);

        $this->assertSame('waiting_blog_price_confirmation', Storage::find($orphanId)->status);
    }

    /** Without a dated history the clock cannot be read, so nothing is guessed. */
    public function test_a_publication_with_no_status_history_is_skipped(): void
    {
        [, , , $publication] = $this->stuckAt('waiting_blog_price_confirmation', 60);

        DB::table('publication_status_events')->where('storage_id', $publication->id)->delete();

        $this->artisan('marketplace:advance-overdue')->assertExitCode(0);

        $this->assertSame('waiting_blog_price_confirmation', $publication->fresh()->status);
    }

    /** Off by default: letting software move Martina's queue is a decision. */
    public function test_it_does_nothing_when_disabled(): void
    {
        config(['linkbuilding.marketplace_deadlines.enabled' => false]);

        [$user, , , $publication] = $this->stuckAt('waiting_blog_price_confirmation', 60);

        $this->artisan('marketplace:advance-overdue')
            ->expectsOutputToContain('Disabled')
            ->assertExitCode(0);

        $this->assertSame('waiting_blog_price_confirmation', $publication->fresh()->status);
        $this->assertSame(473, $this->ledger()->balance($user));
    }

    /** --dry-run reports even while disabled, so the effect can be inspected first. */
    public function test_dry_run_reports_without_changing_anything(): void
    {
        config(['linkbuilding.marketplace_deadlines.enabled' => false]);

        [$user, , , $publication] = $this->stuckAt('waiting_blog_price_confirmation', 60);

        $this->artisan('marketplace:advance-overdue', ['--dry-run' => true])
            ->expectsOutputToContain('DRY RUN')
            ->expectsOutputToContain('publisher never confirmed')
            ->assertExitCode(0);

        $this->assertSame('waiting_blog_price_confirmation', $publication->fresh()->status);
        $this->assertSame(473, $this->ledger()->balance($user));
    }

    /** Running twice must not double-handle anything. */
    public function test_running_twice_is_safe(): void
    {
        [$user, $account, , $publication] = $this->stuckAt('waiting_blog_price_confirmation', 5);

        $this->artisan('marketplace:advance-overdue')->assertExitCode(0);
        $this->artisan('marketplace:advance-overdue')->assertExitCode(0);

        $this->assertSame('publisher_disappeared', $publication->fresh()->status);
        $this->assertSame(1000, $this->ledger()->balance($user));
        $this->assertSame(0, $this->ledger()->heldTotal($account->fresh()));
    }
}
