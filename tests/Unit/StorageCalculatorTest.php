<?php

namespace Tests\Unit;

use App\Services\StorageCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Derived totals for a publication.
 *
 * StorageCalculator is pure array math, so this stays a plain PHPUnit test —
 * this repo's .env points at LIVE PRODUCTION and nothing in the unit suite may
 * risk opening a connection.
 *
 * The property the Link Builder € change rests on: blank or 0 must leave
 * total_cost exactly as it was, which is why no existing publication needed
 * backfilling.
 */
class StorageCalculatorTest extends TestCase
{
    /** The new third term is simply added to the cost base. */
    public function test_link_builder_amount_is_added_to_total_cost(): void
    {
        $data = [
            'publisher_amount' => 100,
            'copy_nr' => 30,
            'link_builder_amount' => 25,
        ];

        StorageCalculator::apply($data);

        $this->assertSame(155.0, $data['total_cost']);
    }

    /** Blank, zero, empty string or absent — all behave exactly as before. */
    public function test_empty_link_builder_amount_leaves_total_cost_unchanged(): void
    {
        foreach ([null, 0, '0', ''] as $empty) {
            $data = [
                'publisher_amount' => 100,
                'copy_nr' => 30,
                'link_builder_amount' => $empty,
            ];

            StorageCalculator::apply($data);

            $this->assertSame(130.0, $data['total_cost'], 'link builder amount: '.var_export($empty, true));
        }

        // A row saved before the column existed carries no key at all.
        $legacy = ['publisher_amount' => 100, 'copy_nr' => 30];
        StorageCalculator::apply($legacy);
        $this->assertSame(130.0, $legacy['total_cost']);
    }

    /** Profit is revenue minus the *new* cost, so it drops by the amount paid. */
    public function test_profit_absorbs_the_link_builder_cost(): void
    {
        $data = [
            'publisher_amount' => 100,
            'copy_nr' => 30,
            'link_builder_amount' => 25,
            'menford' => 200,
            'client_copy' => 50,
        ];

        StorageCalculator::apply($data);

        $this->assertSame(250.0, $data['total_revenues']);
        $this->assertSame(155.0, $data['total_cost']);
        $this->assertSame(95.0, $data['profit']);
    }

    /** The legacy `publisher` key is still honoured alongside the new term. */
    public function test_legacy_publisher_key_still_works(): void
    {
        $data = [
            'publisher' => 80,
            'copy_nr' => 20,
            'link_builder_amount' => 10,
        ];

        StorageCalculator::apply($data);

        $this->assertSame(110.0, $data['total_cost']);
    }

    /** setPrice still lands total_revenues on the asked-for price. */
    public function test_set_price_is_unaffected_by_the_new_cost_term(): void
    {
        $data = [
            'publisher_amount' => 100,
            'copy_nr' => 30,
            'link_builder_amount' => 25,
            'client_copy' => 40,
        ];

        StorageCalculator::setPrice($data, 300);

        $this->assertSame(260.0, (float) $data['menford']);
        $this->assertSame(300.0, $data['total_revenues']);
        $this->assertSame(155.0, $data['total_cost']);
        $this->assertSame(145.0, $data['profit']);
    }
}
