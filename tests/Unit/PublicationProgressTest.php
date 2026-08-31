<?php

namespace Tests\Unit;

use App\Support\PublicationProgress as P;
use PHPUnit\Framework\TestCase;

/**
 * The internal-status → customer-tracker translation.
 *
 * The rule customers depend on: an internal status they were never meant to see
 * must never appear, and must never reset the progress they can already see.
 */
class PublicationProgressTest extends TestCase
{
    public function test_the_five_approved_steps_in_order(): void
    {
        $this->assertSame([
            'Order Submitted',
            'Publisher Confirmation',
            'Article Ready',
            'Article Approved',
            'Article Published',
        ], array_values(P::STEPS));
    }

    /** A new publication starts at "Waiting Blog Price Confirmation" = step 1 done. */
    public function test_a_new_publication_shows_order_submitted_complete(): void
    {
        $t = P::build('waiting_blog_price_confirmation');

        $this->assertTrue($t['steps'][0]['done']);
        $this->assertFalse($t['steps'][1]['done']);
        $this->assertNull($t['exception']);
    }

    /** The off-by-one: a step completes when the internal status moves PAST it. */
    public function test_each_internal_status_completes_the_step_before_it(): void
    {
        $cases = [
            'waiting_blog_price_confirmation' => 1,
            'waiting_copywriter' => 2,
            'waiting_client_article_approval' => 3,
            'waiting_blog_publication' => 4,
            'article_published' => 5,
        ];

        foreach ($cases as $status => $expected) {
            $done = count(array_filter(P::build($status)['steps'], fn ($s) => $s['done']));
            $this->assertSame($expected, $done, "status: {$status}");
        }
    }

    public function test_a_published_article_completes_every_step(): void
    {
        foreach (P::build('article_published')['steps'] as $step) {
            $this->assertTrue($step['done'], $step['key']);
        }
    }

    /* ------------------------------------------------ exceptions */

    public function test_refusals_and_disappearance_raise_a_red_flag(): void
    {
        $this->assertSame('Blog Refused to Publish', P::build('high_price')['exception']);
        $this->assertSame('Publisher Disappeared', P::build('publisher_disappeared')['exception']);
    }

    /**
     * publisher_refused was not in the written spec, but leaving it unmapped
     * would strand a dead order on "in progress" forever.
     */
    public function test_publisher_refused_also_raises_the_refusal_flag(): void
    {
        $this->assertSame('Blog Refused to Publish', P::build('publisher_refused')['exception']);
        $this->assertTrue(P::isException('publisher_refused'));
    }

    /** An exception freezes progress rather than inventing or resetting it. */
    public function test_an_exception_keeps_the_progress_already_reached(): void
    {
        $timestamps = ['order_submitted' => '2026-08-01', 'publisher_confirmation' => '2026-08-02'];

        $t = P::build('publisher_disappeared', $timestamps);

        $this->assertSame('Publisher Disappeared', $t['exception']);
        $this->assertTrue($t['steps'][0]['done']);
        $this->assertTrue($t['steps'][1]['done']);
        $this->assertFalse($t['steps'][2]['done']);
    }

    /* ------------------------------------------------ internal-only statuses */

    /** The eight statuses Simone ruled out must never surface to a customer. */
    public function test_internal_only_statuses_are_not_exceptions_and_not_steps(): void
    {
        $internalOnly = [
            'waiting_client_approval', 'accepted', 'potential_substitute',
            'requirements_not_met', 'out_of_topic', 'already_used_by_client', 'blog_terms',
        ];

        foreach ($internalOnly as $status) {
            $this->assertNull(P::reached($status), "reached: {$status}");
            $this->assertNull(P::exception($status), "exception: {$status}");
        }
    }

    /** …and they hold the progress already shown, rather than resetting it. */
    public function test_an_internal_only_status_holds_the_last_reached_step(): void
    {
        $timestamps = [
            'order_submitted' => '2026-08-01',
            'publisher_confirmation' => '2026-08-02',
            'article_ready' => '2026-08-03',
        ];

        $t = P::build('potential_substitute', $timestamps);

        $done = count(array_filter($t['steps'], fn ($s) => $s['done']));
        $this->assertSame(3, $done);
        $this->assertNull($t['exception']);
    }

    /** No internal status name may ever appear in customer-facing output. */
    public function test_no_internal_slug_leaks_into_the_tracker(): void
    {
        foreach (['potential_substitute', 'requirements_not_met', 'accepted', 'blog_terms', null, ''] as $status) {
            $t = P::build($status);
            $rendered = json_encode($t);

            $this->assertStringNotContainsString('potential_substitute', $rendered);
            $this->assertStringNotContainsString('requirements_not_met', $rendered);
            $this->assertStringNotContainsString('blog_terms', $rendered);
        }
    }

    public function test_unknown_and_null_statuses_are_safe(): void
    {
        foreach ([null, '', '0', 'not_a_status'] as $status) {
            $t = P::build($status);

            $this->assertCount(5, $t['steps']);
            $this->assertNull($t['exception']);
            $this->assertSame(0, count(array_filter($t['steps'], fn ($s) => $s['done'])));
        }
    }

    public function test_timestamps_are_carried_through_to_the_steps(): void
    {
        $t = P::build('waiting_copywriter', [
            'order_submitted' => '2026-08-01 10:00',
            'publisher_confirmation' => '2026-08-02 11:00',
        ]);

        $this->assertSame('2026-08-01 10:00', $t['steps'][0]['at']);
        $this->assertSame('2026-08-02 11:00', $t['steps'][1]['at']);
        $this->assertNull($t['steps'][2]['at']);
    }

    public function test_step_key_lookup(): void
    {
        $this->assertSame('order_submitted', P::stepKeyFor('waiting_blog_price_confirmation'));
        $this->assertSame('article_published', P::stepKeyFor('article_published'));
        $this->assertNull(P::stepKeyFor('potential_substitute'));
    }

    /**
     * A legacy order — placed before publications were opened automatically —
     * has no publication at all. It must still show "Order Submitted" done,
     * because that step is a fact of the order itself.
     */
    public function test_an_order_with_no_publication_still_shows_it_was_submitted(): void
    {
        $t = P::build(null, ['order_submitted' => 'Jan 3, 2026 - 10:00']);

        $this->assertTrue($t['steps'][0]['done']);
        $this->assertSame('Jan 3, 2026 - 10:00', $t['steps'][0]['at']);
        $this->assertFalse($t['steps'][1]['done']);
        $this->assertNull($t['exception']);
    }

    /** With nothing known at all, nothing is claimed as done. */
    public function test_no_status_and_no_dates_completes_nothing(): void
    {
        $t = P::build(null);

        foreach ($t['steps'] as $step) {
            $this->assertFalse($step['done']);
            $this->assertNull($step['at']);
        }
    }

    /** stepKeyFor drives the dating of steps, so pin the mapping down. */
    public function test_step_key_for_maps_statuses_to_the_step_they_complete(): void
    {
        $this->assertSame('order_submitted', P::stepKeyFor('waiting_blog_price_confirmation'));
        $this->assertSame('publisher_confirmation', P::stepKeyFor('waiting_copywriter'));
        $this->assertSame('article_published', P::stepKeyFor('article_published'));
        $this->assertNull(P::stepKeyFor('accepted'));
        $this->assertNull(P::stepKeyFor(null));
    }

    /**
     * The warning Martina sees names the CONSEQUENCE, not the status. The two
     * vocabularies are offset, so "Waiting Copywriter" must announce itself as
     * completing "Publisher Confirmation".
     */
    public function test_customer_impact_names_the_step_the_status_completes(): void
    {
        $this->assertSame('"Publisher Confirmation" marked complete', P::customerImpact('waiting_copywriter'));
        $this->assertSame('"Article Ready" marked complete', P::customerImpact('waiting_client_article_approval'));
        $this->assertSame('a red "Publisher Disappeared" notice', P::customerImpact('publisher_disappeared'));
    }

    /** Internal-only statuses have no impact, so they must never raise a warning. */
    public function test_internal_only_statuses_have_no_customer_impact(): void
    {
        foreach (['accepted', 'potential_substitute', 'waiting_client_approval',
            'requirements_not_met', 'out_of_topic', 'already_used_by_client',
            'blog_terms', null, 'nonsense'] as $silent) {
            $this->assertNull(P::customerImpact($silent), $silent.' must not warn');
        }
    }

    /**
     * The map the front-end warns from must cover every visible status and
     * nothing else — a status added to one table and not the other would
     * either warn wrongly or slip out to a customer silently.
     */
    public function test_impact_map_covers_exactly_the_customer_visible_statuses(): void
    {
        $map = P::impactMap();

        $this->assertSame([
            'waiting_blog_price_confirmation',
            'waiting_copywriter',
            'waiting_client_article_approval',
            'waiting_blog_publication',
            'article_published',
            'high_price',
            'publisher_refused',
            'publisher_disappeared',
        ], array_keys($map));

        foreach ($map as $slug => $message) {
            $this->assertNotEmpty($message, $slug.' needs a message');
        }
    }
}
