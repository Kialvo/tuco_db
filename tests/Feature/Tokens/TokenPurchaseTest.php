<?php

namespace Tests\Feature\Tokens;

use App\Models\TokenPurchase;
use App\Models\TokenTransaction;
use App\Services\Payments\FakeGateway;
use App\Services\Payments\InvalidWebhookSignature;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\WebhookEvent;
use App\Services\Tokens\TokenLedger;
use App\Services\Tokens\TokenPurchaseService;

/**
 * Buying tokens end to end, with no Stripe account and no network: the fake
 * gateway stands in for the hosted checkout and signs its own callbacks.
 */
class TokenPurchaseTest extends TokenTestCase
{
    private function service(): TokenPurchaseService
    {
        return app(TokenPurchaseService::class);
    }

    private function gateway(): FakeGateway
    {
        return app(PaymentGateway::class);
    }

    /** Nothing is credited until the gateway says the money arrived. */
    public function test_starting_a_purchase_credits_nothing(): void
    {
        $user = $this->makeUser();

        [$purchase, $session] = $this->service()->startPurchase(
            $user, 'pro', 'EUR', 'https://x.test/ok', 'https://x.test/no'
        );

        $this->assertSame(TokenPurchase::STATUS_PENDING, $purchase->status);
        $this->assertNotEmpty($session->id);
        $this->assertSame($session->id, $purchase->fresh()->gateway_session_id);
        $this->assertSame(0, app(TokenLedger::class)->balance($user));
        $this->assertSame(0, TokenTransaction::count());
    }

    /** The price comes from config, never from the caller. */
    public function test_the_amount_charged_is_taken_from_the_package_definition(): void
    {
        $user = $this->makeUser();

        [$eur] = $this->service()->startPurchase($user, 'pro', 'EUR', 'https://x.test/ok', 'https://x.test/no');
        [$usd] = $this->service()->startPurchase($user, 'pro', 'USD', 'https://x.test/ok', 'https://x.test/no');

        $this->assertSame(config('tokens.packages.pro.prices.EUR'), (int) $eur->amount_minor);
        $this->assertSame(config('tokens.packages.pro.prices.USD'), (int) $usd->amount_minor);
    }

    /**
     * THE rule of the currency design: the same package yields the same tokens
     * whatever currency was paid in. Tokens are never derived from the money.
     */
    public function test_currency_does_not_change_how_many_tokens_you_get(): void
    {
        $eurUser = $this->makeUser();
        $usdUser = $this->makeUser();

        $this->completePurchase($eurUser, 'pro', 'EUR');
        $this->completePurchase($usdUser, 'pro', 'USD');

        $ledger = app(TokenLedger::class);
        $expected = config('tokens.packages.pro.tokens') + config('tokens.packages.pro.bonus');

        $this->assertSame($expected, $ledger->balance($eurUser));
        $this->assertSame($expected, $ledger->balance($usdUser));
        $this->assertSame($ledger->balance($eurUser), $ledger->balance($usdUser));
    }

    public function test_a_paid_webhook_credits_tokens_and_bonus_separately(): void
    {
        $user = $this->makeUser();

        $purchase = $this->completePurchase($user, 'pro', 'EUR');

        $this->assertSame(TokenPurchase::STATUS_PAID, $purchase->status);
        $this->assertNotNull($purchase->paid_at);

        $types = TokenTransaction::orderBy('id')->pluck('type')->all();
        $this->assertSame([TokenTransaction::TYPE_PURCHASE, TokenTransaction::TYPE_BONUS], $types);

        $this->assertSame(1000, (int) TokenTransaction::where('type', TokenTransaction::TYPE_PURCHASE)->value('amount'));
        $this->assertSame(50, (int) TokenTransaction::where('type', TokenTransaction::TYPE_BONUS)->value('amount'));
    }

    public function test_a_package_without_a_bonus_writes_no_bonus_row(): void
    {
        $user = $this->makeUser();

        $this->completePurchase($user, 'starter', 'EUR');

        $this->assertSame(250, app(TokenLedger::class)->balance($user));
        $this->assertSame(0, TokenTransaction::where('type', TokenTransaction::TYPE_BONUS)->count());
    }

    /** Gateways retry. Replaying the same webhook must not pay twice. */
    public function test_replaying_the_same_webhook_credits_only_once(): void
    {
        $user = $this->makeUser();

        [$purchase] = $this->service()->startPurchase($user, 'pro', 'EUR', 'https://x.test/ok', 'https://x.test/no');

        $event = new WebhookEvent('evt_1', WebhookEvent::PAYMENT_SUCCEEDED, $purchase->gateway_session_id, 'pay_1');

        $this->service()->applyEvent($event);
        $this->service()->applyEvent($event);
        $this->service()->applyEvent($event);

        $this->assertSame(1050, app(TokenLedger::class)->balance($user));
        $this->assertSame(2, TokenTransaction::count());   // tokens + bonus, once
    }

    /** Even a different event id for the same purchase must not double-credit. */
    public function test_two_different_events_for_one_purchase_still_credit_once(): void
    {
        $user = $this->makeUser();
        [$purchase] = $this->service()->startPurchase($user, 'standard', 'EUR', 'https://x.test/ok', 'https://x.test/no');

        $this->service()->applyEvent(new WebhookEvent('evt_a', WebhookEvent::PAYMENT_SUCCEEDED, $purchase->gateway_session_id, 'pay_1'));
        $this->service()->applyEvent(new WebhookEvent('evt_b', WebhookEvent::PAYMENT_SUCCEEDED, $purchase->gateway_session_id, 'pay_1'));

        $this->assertSame(515, app(TokenLedger::class)->balance($user));
    }

    public function test_a_failed_payment_credits_nothing(): void
    {
        $user = $this->makeUser();
        [$purchase] = $this->service()->startPurchase($user, 'pro', 'EUR', 'https://x.test/ok', 'https://x.test/no');

        $this->service()->applyEvent(new WebhookEvent('evt_f', WebhookEvent::PAYMENT_FAILED, $purchase->gateway_session_id, null));

        $this->assertSame(TokenPurchase::STATUS_FAILED, $purchase->fresh()->status);
        $this->assertSame(0, app(TokenLedger::class)->balance($user));
    }

    /** A late failure for an already-settled payment must not strip tokens. */
    public function test_a_failure_arriving_after_success_does_not_revoke_tokens(): void
    {
        $user = $this->makeUser();
        [$purchase] = $this->service()->startPurchase($user, 'pro', 'EUR', 'https://x.test/ok', 'https://x.test/no');

        $this->service()->applyEvent(new WebhookEvent('evt_ok', WebhookEvent::PAYMENT_SUCCEEDED, $purchase->gateway_session_id, 'pay_1'));
        $this->service()->applyEvent(new WebhookEvent('evt_late', WebhookEvent::PAYMENT_FAILED, $purchase->gateway_session_id, null));

        $this->assertSame(TokenPurchase::STATUS_PAID, $purchase->fresh()->status);
        $this->assertSame(1050, app(TokenLedger::class)->balance($user));
    }

    public function test_a_refund_reverses_exactly_what_was_granted(): void
    {
        $user = $this->makeUser();
        $purchase = $this->completePurchase($user, 'pro', 'EUR');

        $this->service()->applyEvent(new WebhookEvent('evt_r', WebhookEvent::PAYMENT_REFUNDED, $purchase->gateway_session_id, null));

        $this->assertSame(TokenPurchase::STATUS_REFUNDED, $purchase->fresh()->status);
        $this->assertSame(0, app(TokenLedger::class)->balance($user));
    }

    /**
     * A refund for tokens that are already spent cannot silently go negative:
     * it raises, so a human decides what happens.
     */
    public function test_refunding_already_spent_tokens_is_refused(): void
    {
        $user = $this->makeUser();
        $purchase = $this->completePurchase($user, 'pro', 'EUR');

        $ledger = app(TokenLedger::class);
        $ledger->debit($ledger->accountFor($user), 900, TokenTransaction::TYPE_SPEND, 'order:1:spend');

        $this->expectException(\App\Exceptions\InsufficientTokens::class);

        $this->service()->applyEvent(new WebhookEvent('evt_r', WebhookEvent::PAYMENT_REFUNDED, $purchase->gateway_session_id, null));
    }

    public function test_an_unknown_session_is_ignored(): void
    {
        $this->assertNull(
            $this->service()->applyEvent(new WebhookEvent('evt_x', WebhookEvent::PAYMENT_SUCCEEDED, 'fake_cs_nope', null))
        );
    }

    public function test_unknown_packages_and_currencies_are_rejected(): void
    {
        $user = $this->makeUser();

        $this->expectException(\InvalidArgumentException::class);
        $this->service()->startPurchase($user, 'does-not-exist', 'EUR', 'https://x.test/ok', 'https://x.test/no');
    }

    public function test_an_unsupported_currency_is_rejected(): void
    {
        $user = $this->makeUser();

        $this->expectException(\InvalidArgumentException::class);
        $this->service()->startPurchase($user, 'pro', 'JPY', 'https://x.test/ok', 'https://x.test/no');
    }

    /* ---------------------------------------------------------------- webhook signing */

    public function test_a_correctly_signed_webhook_is_accepted(): void
    {
        $gateway = $this->gateway();
        [$payload, $headers] = $gateway->makeSignedWebhook(WebhookEvent::PAYMENT_SUCCEEDED, 'fake_cs_abc');

        $event = $gateway->parseWebhook($payload, $headers);

        $this->assertSame(WebhookEvent::PAYMENT_SUCCEEDED, $event->type);
        $this->assertSame('fake_cs_abc', $event->sessionId);
    }

    /** An endpoint that trusts its payload is a free-tokens API. */
    public function test_an_unsigned_webhook_is_rejected(): void
    {
        $this->expectException(InvalidWebhookSignature::class);

        $this->gateway()->parseWebhook('{"id":"evt","type":"payment.succeeded"}', []);
    }

    public function test_a_tampered_webhook_is_rejected(): void
    {
        $gateway = $this->gateway();
        [$payload, $headers] = $gateway->makeSignedWebhook(WebhookEvent::PAYMENT_SUCCEEDED, 'fake_cs_abc');

        // Same signature, different body — the attacker's usual move.
        $tampered = str_replace('fake_cs_abc', 'fake_cs_someone_elses', $payload);

        $this->expectException(InvalidWebhookSignature::class);
        $gateway->parseWebhook($tampered, $headers);
    }

    /* ---------------------------------------------------------------- helpers */

    private function completePurchase($user, string $package, string $currency): TokenPurchase
    {
        [$purchase] = $this->service()->startPurchase($user, $package, $currency, 'https://x.test/ok', 'https://x.test/no');

        $this->service()->applyEvent(new WebhookEvent(
            'evt_'.$purchase->id, WebhookEvent::PAYMENT_SUCCEEDED, $purchase->gateway_session_id, 'pay_'.$purchase->id
        ));

        return $purchase->fresh();
    }
}
