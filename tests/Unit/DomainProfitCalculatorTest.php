<?php

namespace Tests\Unit;

use App\Services\DomainProfitCalculator as Calc;
use PHPUnit\Framework\TestCase;

/**
 * The single definition of a domain's Profit.
 *
 * Pure array/scalar math, so this stays a plain PHPUnit test — this repo's
 * .env points at LIVE PRODUCTION and nothing in the unit suite may risk
 * opening a connection. The caller converts USD to EUR before calling, which
 * is exactly why the conversion is not exercised here.
 */
class DomainProfitCalculatorTest extends TestCase
{
    /** The formula, on a plain euro domain. */
    public function test_profit_is_evaluation_minus_publisher_minus_link_builder(): void
    {
        $this->assertSame(70.0, Calc::calculate(200, 100.0, 30));
    }

    /**
     * Blank Kialvo Evaluation stays blank.
     *
     * 47 domains have no evaluation and show an empty Profit today. Without
     * this guard they would quietly become negative (0 − publisher − link
     * builder) the next time anyone saved them.
     */
    public function test_blank_evaluation_yields_null_not_a_negative_number(): void
    {
        $this->assertNull(Calc::calculate(null, 100.0, 30));
        $this->assertNull(Calc::calculate('', 100.0, 30));
    }

    /** An empty link builder amount behaves exactly as 0. */
    public function test_empty_link_builder_amount_is_treated_as_zero(): void
    {
        foreach ([null, 0, '0', ''] as $empty) {
            $this->assertSame(100.0, Calc::calculate(200, 100.0, $empty), 'link builder: '.var_export($empty, true));
        }
    }

    /** No publisher price behaves as 0, as every old formula did. */
    public function test_missing_publisher_price_counts_as_zero(): void
    {
        $this->assertSame(200.0, Calc::calculate(200, null, null));
        $this->assertSame(170.0, Calc::calculate(200, null, 30));
    }

    /**
     * The caller passes the EUR publisher price, so a USD domain's profit is
     * derived from converted euros — never from the raw dollar figure, and
     * never rescaled afterwards.
     */
    public function test_uses_the_converted_publisher_price_it_is_given(): void
    {
        // 120 USD at 0.86 = 103.2 EUR; evaluation 300, link builder 50.
        $this->assertEqualsWithDelta(146.8, Calc::calculate(300, 120 * 0.86, 50), 0.0001);
    }

    /** Costs above the evaluation give a negative figure, not zero. */
    public function test_profit_can_be_negative(): void
    {
        $this->assertSame(-40.0, Calc::calculate(100, 120.0, 20));
    }

    /** Values arrive from the DB as decimal strings; they must still add up. */
    public function test_numeric_strings_from_the_database_are_handled(): void
    {
        $this->assertSame(70.0, Calc::calculate('200.00', 100.0, '30'));
    }
}
