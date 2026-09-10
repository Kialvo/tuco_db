<?php

namespace Tests\Feature\Tokens;

/**
 * The kill switch, verified from the default rather than from a test override.
 *
 * This class deliberately extends TokenTestCase, NOT TokenHoldTestCase: the
 * latter switches spending on so the hold machinery can be exercised, which
 * would make an assertion about the default meaningless here.
 *
 * Why this matters enough to test: the day this code reaches production,
 * `OrderController::submit()` holds tokens. Guests have no balance, so if the
 * flag defaulted to ON, the deploy alone would stop every real customer from
 * placing an order — a silent outage caused by shipping, not by a decision.
 */
class SpendingDisabledTest extends TokenTestCase
{
    /** Shipping this code must change nothing until someone says so. */
    public function test_spending_is_off_unless_explicitly_switched_on(): void
    {
        $this->assertFalse(
            (bool) config('tokens.spending_enabled'),
            'tokens.spending_enabled must default to false — a deploy must never '
            .'switch the marketplace to prepaid on its own.'
        );
    }

    /** The gateway driver has the same property, and for the same reason. */
    public function test_the_payments_driver_defaults_to_the_fake_one(): void
    {
        $this->assertSame(
            'fake',
            config('tokens.driver'),
            'A missing PAYMENTS_DRIVER must never put a real gateway in front of a customer.'
        );
    }

    /**
     * The flag gates NEW holds only. Settlement stays live either way, so
     * switching spending off cannot strand tokens already committed to an
     * order that is still running.
     */
    public function test_settlement_is_not_gated_by_the_spending_flag(): void
    {
        $settlement = new \ReflectionClass(\App\Services\Tokens\OrderSettlement::class);
        $source = file_get_contents($settlement->getFileName());

        $this->assertStringNotContainsString(
            'spending_enabled',
            $source,
            'OrderSettlement must not consult the spending flag: holds taken while it '
            .'was on still have to settle after it is switched off.'
        );
    }
}
