<?php

namespace Tests\Unit;

use App\Http\Controllers\StatsController;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * StatsController::buildDecisionTrend() — the company × month matrix behind the
 * "Approval / Rejection Rate over time" widget on Campaigns Stats.
 *
 * The method is pure (arrays in, arrays out, no DB), so it is exercised directly
 * through reflection rather than by booting the app.
 */
class DecisionTrendTest extends TestCase
{
    private function build(array $byMonth, array $byClient, ?string $from = null, ?string $to = null): array
    {
        $method = new ReflectionMethod(StatsController::class, 'buildDecisionTrend');
        $method->setAccessible(true);

        return $method->invoke(
            new StatsController,
            $byMonth,
            $byClient,
            $from ? Carbon::parse($from)->startOfDay() : null,
            $to ? Carbon::parse($to)->startOfDay() : null
        );
    }

    /** A client row as publicationDecisions() ranks them. */
    private function client(string $name): array
    {
        return ['name' => $name];
    }

    public function test_month_axis_is_continuous_and_fills_gaps_with_zero(): void
    {
        // March is missing from the data entirely — it must still appear on the
        // axis, as a zero bucket, or the line would silently skip a month.
        $trend = $this->build(
            ['Acme' => [
                '2026-01' => ['approved' => 3, 'rejected' => 1],
                '2026-04' => ['approved' => 5, 'rejected' => 0],
            ]],
            [$this->client('Acme')]
        );

        $this->assertSame(['Jan 2026', 'Feb 2026', 'Mar 2026', 'Apr 2026'], $trend['months']);
        $this->assertSame([3, 0, 0, 5], $trend['clients'][0]['approved']);
        $this->assertSame([1, 0, 0, 0], $trend['clients'][0]['rejected']);
    }

    public function test_picked_range_defines_the_axis_even_where_there_is_no_data(): void
    {
        // The picker is the contract: an empty range must draw its own months
        // rather than collapse onto whatever data happens to exist.
        $trend = $this->build(
            ['Acme' => ['2026-05' => ['approved' => 2, 'rejected' => 2]]],
            [$this->client('Acme')],
            '2026-04-10',
            '2026-06-30'
        );

        $this->assertSame(['Apr 2026', 'May 2026', 'Jun 2026'], $trend['months']);
        $this->assertSame([0, 2, 0], $trend['clients'][0]['approved']);
    }

    public function test_overall_is_the_sum_across_clients_per_bucket(): void
    {
        $trend = $this->build(
            [
                'Acme' => ['2026-01' => ['approved' => 3, 'rejected' => 1]],
                'Globex' => ['2026-01' => ['approved' => 4, 'rejected' => 2]],
            ],
            [$this->client('Acme'), $this->client('Globex')]
        );

        $this->assertSame([7], $trend['overall']['approved']);
        $this->assertSame([3], $trend['overall']['rejected']);
    }

    public function test_client_order_follows_the_ranked_input(): void
    {
        // The widget's default selection is "the first N", so the order the page
        // already ranks by (decided volume) has to survive into the payload.
        $trend = $this->build(
            [
                'Small' => ['2026-01' => ['approved' => 1, 'rejected' => 0]],
                'Big' => ['2026-01' => ['approved' => 40, 'rejected' => 10]],
            ],
            [$this->client('Big'), $this->client('Small')]
        );

        $this->assertSame(['Big', 'Small'], array_column($trend['clients'], 'name'));
        $this->assertSame(50, $trend['clients'][0]['decided']);
        $this->assertSame(1, $trend['clients'][1]['decided']);
    }

    public function test_a_client_with_no_dated_decision_is_dropped(): void
    {
        // A client that only ever had pending proposals (or rows with no
        // created_at) would draw an all-null line and clutter the legend.
        $trend = $this->build(
            ['Acme' => ['2026-01' => ['approved' => 3, 'rejected' => 1]]],
            [$this->client('Acme'), $this->client('PendingOnly')]
        );

        $this->assertSame(['Acme'], array_column($trend['clients'], 'name'));
    }

    public function test_no_data_and_no_range_yields_an_empty_payload(): void
    {
        $trend = $this->build([], []);

        $this->assertSame([], $trend['months']);
        $this->assertSame([], $trend['clients']);
        $this->assertSame([], $trend['overall']['approved']);
    }

    public function test_a_reversed_range_still_produces_one_month(): void
    {
        // StatsDateRange normalises the picker, but the method must not emit a
        // negative-length axis if it is ever called with bounds out of order.
        $trend = $this->build([], [], '2026-06-01', '2026-03-01');

        $this->assertCount(1, $trend['months']);
        $this->assertSame(['Mar 2026'], $trend['months']);
    }

    /**
     * Regression: the month axis must not depend on what today's date is.
     *
     * Carbon::createFromFormat('Y-m', ...) fills the missing DAY from today, so
     * on the 31st "2026-04" became May 1st and the axis silently gained a month.
     * Every 30-day month and February were affected, on the 29th-31st. Freezing
     * "now" to a 31st keeps this caught on the other 28 days of the month too.
     */
    public function test_month_axis_is_unaffected_by_todays_day_of_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-31 12:00:00'));

        try {
            $trend = $this->build(
                ['Acme' => [
                    '2026-02' => ['approved' => 1, 'rejected' => 0],
                    '2026-04' => ['approved' => 2, 'rejected' => 0],
                ]],
                [$this->client('Acme')]
            );

            $this->assertSame(['Feb 2026', 'Mar 2026', 'Apr 2026'], $trend['months']);
            $this->assertSame([1, 0, 2], $trend['clients'][0]['approved']);
        } finally {
            Carbon::setTestNow();
        }
    }
}
