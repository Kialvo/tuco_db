<?php

namespace Tests\Feature\Tokens;

use App\Models\TokenPurchase;
use App\Services\Payments\WebhookEvent;
use App\Services\Tokens\TokenLedger;
use App\Services\Tokens\TokenPurchaseService;

/**
 * Custom top-up amounts.
 *
 * The client's case: a placement can cost EUR 130, so a EUR 250 minimum was
 * making people buy more credit than the job needed.
 */
class CustomAmountTest extends TokenTestCase
{
    private function service(): TokenPurchaseService
    {
        return app(TokenPurchaseService::class);
    }

    public function test_the_reported_case_a_130_euro_top_up_works(): void
    {
        $user = $this->makeUser();

        [$purchase] = $this->service()->startCustomPurchase($user, 130, 'EUR', 'https://x.test/ok', 'https://x.test/no');

        $this->assertSame(130, (int) $purchase->tokens);
        $this->assertSame(13000, (int) $purchase->amount_minor);   // EUR 130.00
        $this->assertSame('custom', $purchase->package_key);
        $this->assertSame(0, (int) $purchase->bonus_tokens);
    }

    /** 1 token = EUR 1: the euro amount and the token count are the same number. */
    public function test_euro_amount_always_equals_the_token_count(): void
    {
        $user = $this->makeUser();

        foreach ([50, 130, 777, 10000] as $tokens) {
            [$purchase] = $this->service()->startCustomPurchase($user, $tokens, 'EUR', 'https://x.test/ok', 'https://x.test/no');
            $this->assertSame($tokens * 100, (int) $purchase->amount_minor, "tokens={$tokens}");
        }
    }

    /** USD uses the published multiplier, not a live rate. */
    public function test_usd_uses_the_configured_multiplier(): void
    {
        $user = $this->makeUser();

        [$purchase] = $this->service()->startCustomPurchase($user, 130, 'USD', 'https://x.test/ok', 'https://x.test/no');

        $expected = (int) round(130 * 100 * config('tokens.custom.multipliers.USD'));
        $this->assertSame($expected, (int) $purchase->amount_minor);
        $this->assertSame('USD', $purchase->currency);

        // Still 130 tokens: currency changes the price, never the credit.
        $this->assertSame(130, (int) $purchase->tokens);
    }

    public function test_paying_a_custom_amount_credits_exactly_that_many_tokens(): void
    {
        $user = $this->makeUser();
        [$purchase] = $this->service()->startCustomPurchase($user, 130, 'EUR', 'https://x.test/ok', 'https://x.test/no');

        $this->service()->applyEvent(new WebhookEvent(
            'evt_c', WebhookEvent::PAYMENT_SUCCEEDED, $purchase->gateway_session_id, 'pay_c'
        ));

        $this->assertSame(130, app(TokenLedger::class)->balance($user));
        $this->assertSame(TokenPurchase::STATUS_PAID, $purchase->fresh()->status);
    }

    public function test_amounts_below_the_minimum_are_refused(): void
    {
        $user = $this->makeUser();
        $min = (int) config('tokens.custom.min_tokens');

        $this->expectException(\InvalidArgumentException::class);
        $this->service()->startCustomPurchase($user, $min - 1, 'EUR', 'https://x.test/ok', 'https://x.test/no');
    }

    public function test_amounts_above_the_maximum_are_refused(): void
    {
        $user = $this->makeUser();
        $max = (int) config('tokens.custom.max_tokens');

        $this->expectException(\InvalidArgumentException::class);
        $this->service()->startCustomPurchase($user, $max + 1, 'EUR', 'https://x.test/ok', 'https://x.test/no');
    }

    public function test_the_boundaries_themselves_are_allowed(): void
    {
        $user = $this->makeUser();

        foreach ([(int) config('tokens.custom.min_tokens'), (int) config('tokens.custom.max_tokens')] as $edge) {
            [$purchase] = $this->service()->startCustomPurchase($user, $edge, 'EUR', 'https://x.test/ok', 'https://x.test/no');
            $this->assertSame($edge, (int) $purchase->tokens);
        }
    }

    public function test_zero_and_negative_amounts_are_refused(): void
    {
        $user = $this->makeUser();

        foreach ([0, -1, -500] as $bad) {
            try {
                $this->service()->startCustomPurchase($user, $bad, 'EUR', 'https://x.test/ok', 'https://x.test/no');
                $this->fail("accepted {$bad} tokens");
            } catch (\InvalidArgumentException) {
            }
        }

        $this->assertSame(0, TokenPurchase::count());
    }

    public function test_an_unsupported_currency_is_refused(): void
    {
        $user = $this->makeUser();

        $this->expectException(\InvalidArgumentException::class);
        $this->service()->startCustomPurchase($user, 130, 'JPY', 'https://x.test/ok', 'https://x.test/no');
    }

    /** The 2,500 tier was replaced, so it must no longer be purchasable. */
    public function test_the_removed_agency_package_is_gone(): void
    {
        $this->assertArrayNotHasKey('agency', config('tokens.packages'));

        $this->expectException(\InvalidArgumentException::class);
        $this->service()->startPurchase($this->makeUser(), 'agency', 'EUR', 'https://x.test/ok', 'https://x.test/no');
    }

    /** Its ceiling is still reachable, just without the bonus. */
    public function test_2500_is_still_buyable_as_a_custom_amount(): void
    {
        $user = $this->makeUser();

        [$purchase] = $this->service()->startCustomPurchase($user, 2500, 'EUR', 'https://x.test/ok', 'https://x.test/no');

        $this->assertSame(2500, (int) $purchase->tokens);
        $this->assertSame(250000, (int) $purchase->amount_minor);
    }
}
