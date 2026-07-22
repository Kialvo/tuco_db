<?php

namespace Tests\Unit;

use App\Support\PublicationStatus;
use App\Support\Statistics;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

/**
 * The site-approval decision funnel that drives the Campaigns Stats page.
 *
 * Deliberately a plain PHPUnit TestCase with a hand-built container rather than
 * Tests\TestCase: this repo's phpunit.xml leaves DB_CONNECTION/DB_DATABASE
 * commented out and .env points at LIVE PRODUCTION, so nothing in the unit suite
 * may risk opening a connection. Only the config repository is bound.
 */
class PublicationDecisionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container;
        $container->instance('config', new Repository([
            'linkbuilding' => require __DIR__.'/../../config/linkbuilding.php',
        ]));
        Container::setInstance($container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_every_status_declares_a_valid_decision(): void
    {
        $valid = config('linkbuilding.publication_decisions');

        foreach (PublicationStatus::all() as $slug => $definition) {
            $this->assertArrayHasKey('decision', $definition, "Status [$slug] is missing a 'decision' key");
            $this->assertContains($definition['decision'], $valid, "Status [$slug] has an unknown decision");
        }
    }

    public function test_client_refusals_are_rejections(): void
    {
        $expected = ['requirements_not_met', 'high_price', 'out_of_topic', 'already_used_by_client'];

        foreach ($expected as $slug) {
            $this->assertSame('rejected', PublicationStatus::decision($slug));
        }

        $this->assertSame($expected, PublicationStatus::slugsByDecision('rejected'));
    }

    public function test_publisher_side_failures_count_as_approved(): void
    {
        // The client had already approved the site; the publisher fell through
        // afterwards. These must never be counted as a client rejection.
        $this->assertSame('approved', PublicationStatus::decision('publisher_refused'));
        $this->assertSame('approved', PublicationStatus::decision('publisher_disappeared'));
    }

    public function test_every_production_group_status_is_approved(): void
    {
        foreach (PublicationStatus::all() as $slug => $definition) {
            if ($definition['group'] === 2) {
                $this->assertSame('approved', PublicationStatus::decision($slug), "Group 2 status [$slug] must be approved");
            }
        }
    }

    public function test_accepted_is_approved_and_waiting_states_are_pending(): void
    {
        $this->assertSame('approved', PublicationStatus::decision('accepted'));
        $this->assertSame('pending', PublicationStatus::decision('waiting_client_approval'));
        $this->assertSame('pending', PublicationStatus::decision('waiting_blog_price_confirmation'));
        $this->assertSame('pending', PublicationStatus::decision('potential_substitute'));
    }

    public function test_unknown_and_legacy_values_fall_back_to_pending(): void
    {
        // 142 production rows carry the legacy status '0'. They must never land
        // in the approved or rejected bucket and skew a rate.
        $this->assertSame('pending', PublicationStatus::decision('0'));
        $this->assertSame('pending', PublicationStatus::decision(''));
        $this->assertSame('pending', PublicationStatus::decision(null));
        $this->assertSame('pending', PublicationStatus::decision('not_a_real_status'));
    }

    public function test_rate_uses_a_decided_only_denominator(): void
    {
        $approved = 70;
        $rejected = 141;
        $decided = $approved + $rejected;

        $this->assertSame(33.2, Statistics::rate($approved, $decided));
        $this->assertSame(66.8, Statistics::rate($rejected, $decided));
        $this->assertSame(100.0, Statistics::rate($approved, $decided) + Statistics::rate($rejected, $decided));
    }

    public function test_rate_is_null_not_zero_when_nothing_is_decided(): void
    {
        // A client we pitched who has not answered yet: "—", never a 0% that
        // reads like a real measurement.
        $this->assertNull(Statistics::rate(0, 0));
        $this->assertNull(Statistics::rate(5, 0));
    }

    public function test_rate_handles_the_full_and_empty_ends(): void
    {
        $this->assertSame(100.0, Statistics::rate(9, 9));
        $this->assertSame(0.0, Statistics::rate(0, 9));
    }
}
