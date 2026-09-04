<?php

namespace Tests\Unit;

use App\Services\DomainPriceCalculator as Calc;
use PHPUnit\Framework\TestCase;

/**
 * The cost base behind Price and Sensitive Topic Price.
 *
 * Only the DB-free parts are exercised: price() and sensitiveTopicPrice() go
 * through MenfordPriceCalculator::calculate(), which resolves a language id
 * through the model, and this repo's .env points at LIVE PRODUCTION.
 *
 * The rate is passed in rather than looked up, which is what makes these
 * assertions possible at all.
 */
class DomainPriceCalculatorTest extends TestCase
{
    private const RATE = 0.86135;

    /** EUR domain: the publisher price is taken as-is. */
    public function test_eur_publisher_price_is_not_converted(): void
    {
        $data = ['publisher_price' => 100, 'currency_code' => 'EUR'];

        $this->assertSame(100.0, Calc::publisherPriceInEur($data, self::RATE));
    }

    /** USD domain: converted from the original, never from the stored EUR column. */
    public function test_usd_publisher_price_uses_the_original_times_the_rate(): void
    {
        $data = [
            'publisher_price' => 86.14,          // already-converted column, must be ignored
            'original_publisher_price' => 100,
            'currency_code' => 'USD',
        ];

        $this->assertEqualsWithDelta(86.135, Calc::publisherPriceInEur($data, self::RATE), 0.0001);
    }

    /**
     * The link builder amount is added AFTER conversion.
     *
     * This is the whole point: it is entered in euros whatever the domain's
     * currency, so a USD domain with 100 publisher + 50 link builder must be
     * 86.135 + 50, never (100 + 50) * rate.
     */
    public function test_link_builder_amount_is_added_after_conversion(): void
    {
        $data = [
            'publisher_price' => 100,
            'original_publisher_price' => 100,
            'currency_code' => 'USD',
            'link_builder_amount' => 50,
        ];

        $this->assertEqualsWithDelta(136.135, Calc::priceBase($data, self::RATE), 0.0001);
        $this->assertNotEqualsWithDelta(150 * self::RATE, Calc::priceBase($data, self::RATE), 0.0001);
    }

    /** On a EUR domain the base is a plain sum. */
    public function test_eur_price_base_is_publisher_plus_link_builder(): void
    {
        $data = ['publisher_price' => 100, 'currency_code' => 'EUR', 'link_builder_amount' => 50];

        $this->assertSame(150.0, Calc::priceBase($data, self::RATE));
    }

    /** Blank or zero link builder leaves the base exactly as it was. */
    public function test_empty_link_builder_amount_changes_nothing(): void
    {
        foreach ([null, 0, '0', ''] as $empty) {
            $data = ['publisher_price' => 100, 'currency_code' => 'EUR', 'link_builder_amount' => $empty];

            $this->assertSame(100.0, Calc::priceBase($data, self::RATE), 'link builder: '.var_export($empty, true));
        }
    }

    /** No publisher price means no price — a link builder amount never invents one. */
    public function test_missing_publisher_price_gives_no_price_base(): void
    {
        $this->assertNull(Calc::priceBase(['currency_code' => 'EUR', 'link_builder_amount' => 50], self::RATE));
        $this->assertNull(Calc::priceBase(['publisher_price' => '', 'currency_code' => 'EUR'], self::RATE));
    }

    /** A USD row with no original price cannot be priced. */
    public function test_usd_without_an_original_price_gives_no_price_base(): void
    {
        $data = ['publisher_price' => 100, 'original_publisher_price' => '', 'currency_code' => 'USD'];

        $this->assertNull(Calc::publisherPriceInEur($data, self::RATE));
    }

    /** No Special Topic Price → Sensitive Topic Price inherits Price. */
    public function test_sensitive_topic_price_falls_back_to_price(): void
    {
        $data = ['price' => 187.0, 'currency_code' => 'EUR', 'special_topic_price' => null];

        $this->assertSame(187.0, Calc::sensitiveTopicPrice($data, self::RATE));
    }

    /** A USD row whose original special topic price is missing falls back too. */
    public function test_sensitive_topic_price_falls_back_when_usd_original_is_missing(): void
    {
        $data = [
            'price' => 187.0,
            'currency_code' => 'USD',
            'special_topic_price' => 120,
            'original_special_topic_price' => '',
        ];

        $this->assertSame(187.0, Calc::sensitiveTopicPrice($data, self::RATE));
    }

    /** Values arrive from the DB and from CSVs as strings. */
    public function test_numeric_strings_are_handled(): void
    {
        $data = ['publisher_price' => '100.00', 'currency_code' => 'EUR', 'link_builder_amount' => '50'];

        $this->assertSame(150.0, Calc::priceBase($data, self::RATE));
    }
}
