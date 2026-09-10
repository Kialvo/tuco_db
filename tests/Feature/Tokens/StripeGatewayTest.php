<?php

namespace Tests\Feature\Tokens;

use App\Models\TokenPurchase;
use App\Models\TokenTransaction;
use App\Services\Payments\InvalidWebhookSignature;
use App\Services\Payments\StripeGateway;
use App\Services\Payments\WebhookEvent;
use App\Services\Tokens\TokenLedger;
use App\Services\Tokens\TokenPurchaseService;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The Stripe gateway, exercised with captured payloads and real signatures.
 *
 * No network and no keys: every assertion here is about the part we own —
 * signature verification and the translation of Stripe's event vocabulary into
 * ours. That translation is where the expensive mistakes live, because getting
 * it wrong means either giving tokens away or silently dropping a refund.
 *
 * createCheckoutSession() is deliberately NOT covered here — it is a call to
 * Stripe's API, and a test that mocks the whole SDK would only assert that we
 * can write a mock. It is covered by the manual sandbox click-through instead.
 */
class StripeGatewayTest extends TokenTestCase
{
    private const SECRET = 'sk_test_fake_secret_for_tests';

    private const WEBHOOK_SECRET = 'whsec_test_only_never_real';

    private function gateway(string $webhookSecret = self::WEBHOOK_SECRET): StripeGateway
    {
        return new StripeGateway(self::SECRET, $webhookSecret);
    }

    /**
     * Build the Stripe-Signature header exactly as Stripe does:
     * HMAC-SHA256 over "<timestamp>.<payload>", keyed with the endpoint secret.
     */
    private function sign(string $payload, string $secret = self::WEBHOOK_SECRET, ?int $timestamp = null): array
    {
        $timestamp ??= time();
        $hmac = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return ['stripe-signature' => "t={$timestamp},v1={$hmac}"];
    }

    private function checkoutCompleted(string $paymentStatus = 'paid', string $session = 'cs_test_abc123'): string
    {
        return json_encode([
            'id' => 'evt_test_'.substr(md5($session.$paymentStatus), 0, 12),
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => $session,
                'object' => 'checkout.session',
                'payment_status' => $paymentStatus,
                'payment_intent' => 'pi_test_999',
            ]],
        ], JSON_THROW_ON_ERROR);
    }

    private function chargeEvent(string $type, string $paymentIntent = 'pi_test_999'): string
    {
        return json_encode([
            'id' => 'evt_test_'.substr(md5($type.$paymentIntent), 0, 12),
            'object' => 'event',
            'type' => $type,
            'data' => ['object' => [
                'id' => 'ch_test_555',
                'object' => str_starts_with($type, 'charge.dispute') ? 'dispute' : 'charge',
                'payment_intent' => $paymentIntent,
            ]],
        ], JSON_THROW_ON_ERROR);
    }

    /* ───────────────────────── credentials ───────────────────────── */

    public function test_it_refuses_to_start_without_a_secret_key(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/STRIPE_SECRET_KEY/');

        new StripeGateway('', self::WEBHOOK_SECRET);
    }

    /** No webhook secret means every delivery fails and nobody is ever credited. */
    public function test_it_refuses_to_start_without_a_webhook_secret(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/STRIPE_WEBHOOK_SECRET/');

        new StripeGateway(self::SECRET, '');
    }

    public function test_it_reports_its_name_as_stripe(): void
    {
        $this->assertSame('stripe', $this->gateway()->name());
    }

    /* ───────────────────────── signatures ───────────────────────── */

    public function test_a_correctly_signed_payload_is_accepted(): void
    {
        $payload = $this->checkoutCompleted();

        $event = $this->gateway()->parseWebhook($payload, $this->sign($payload));

        $this->assertSame(WebhookEvent::PAYMENT_SUCCEEDED, $event->type);
        $this->assertSame('cs_test_abc123', $event->sessionId);
        $this->assertSame('pi_test_999', $event->paymentId);
    }

    /** An endpoint that trusts its own body is a free-tokens API. */
    public function test_an_unsigned_payload_is_rejected(): void
    {
        $this->expectException(InvalidWebhookSignature::class);

        $this->gateway()->parseWebhook($this->checkoutCompleted(), []);
    }

    public function test_an_empty_signature_header_is_rejected(): void
    {
        $this->expectException(InvalidWebhookSignature::class);

        $this->gateway()->parseWebhook($this->checkoutCompleted(), ['stripe-signature' => '']);
    }

    /** The signature covers the body: change one byte and it must not verify. */
    public function test_a_tampered_payload_is_rejected(): void
    {
        $payload = $this->checkoutCompleted();
        $headers = $this->sign($payload);

        $tampered = str_replace('cs_test_abc123', 'cs_test_ATTACKER', $payload);

        $this->expectException(InvalidWebhookSignature::class);

        $this->gateway()->parseWebhook($tampered, $headers);
    }

    /** A signature from somebody else's secret is worth nothing. */
    public function test_a_signature_from_the_wrong_secret_is_rejected(): void
    {
        $payload = $this->checkoutCompleted();
        $headers = $this->sign($payload, 'whsec_a_different_secret_entirely');

        $this->expectException(InvalidWebhookSignature::class);

        $this->gateway()->parseWebhook($payload, $headers);
    }

    /** Replay protection: Stripe's tolerance rejects a very old timestamp. */
    public function test_an_ancient_timestamp_is_rejected(): void
    {
        $payload = $this->checkoutCompleted();
        $headers = $this->sign($payload, self::WEBHOOK_SECRET, time() - 86400);

        $this->expectException(InvalidWebhookSignature::class);

        $this->gateway()->parseWebhook($payload, $headers);
    }

    public function test_header_lookup_is_case_insensitive(): void
    {
        $payload = $this->checkoutCompleted();
        $signed = $this->sign($payload);

        // Symfony hands headers back lower-cased and array-wrapped; a real
        // request may present either shape.
        $event = $this->gateway()->parseWebhook($payload, ['Stripe-Signature' => [$signed['stripe-signature']]]);

        $this->assertSame(WebhookEvent::PAYMENT_SUCCEEDED, $event->type);
    }

    /* ───────────────────────── event mapping ───────────────────────── */

    /**
     * THE trap. A completed session is not a paid session: SEPA and other
     * delayed methods finish the checkout days before the money settles.
     * Treating this as success hands out tokens for money that may never come.
     */
    public function test_a_completed_but_unpaid_session_is_not_a_success(): void
    {
        $payload = $this->checkoutCompleted('unpaid');

        $event = $this->gateway()->parseWebhook($payload, $this->sign($payload));

        $this->assertNotSame(WebhookEvent::PAYMENT_SUCCEEDED, $event->type);
        $this->assertSame('checkout.session.completed.unpaid', $event->type);
    }

    public function test_an_async_payment_succeeding_later_is_a_success(): void
    {
        $payload = json_encode([
            'id' => 'evt_async_ok',
            'object' => 'event',
            'type' => 'checkout.session.async_payment_succeeded',
            'data' => ['object' => [
                'id' => 'cs_test_async',
                'object' => 'checkout.session',
                'payment_status' => 'paid',
                'payment_intent' => 'pi_test_async',
            ]],
        ], JSON_THROW_ON_ERROR);

        $event = $this->gateway()->parseWebhook($payload, $this->sign($payload));

        $this->assertSame(WebhookEvent::PAYMENT_SUCCEEDED, $event->type);
        $this->assertSame('cs_test_async', $event->sessionId);
    }

    public static function failureEvents(): array
    {
        return [
            'async failure' => ['checkout.session.async_payment_failed'],
            'session expired' => ['checkout.session.expired'],
        ];
    }

    #[DataProvider('failureEvents')]
    public function test_failure_shaped_events_map_to_failed(string $type): void
    {
        $payload = json_encode([
            'id' => 'evt_'.md5($type),
            'object' => 'event',
            'type' => $type,
            'data' => ['object' => [
                'id' => 'cs_test_fail',
                'object' => 'checkout.session',
                'payment_status' => 'unpaid',
                'payment_intent' => 'pi_test_fail',
            ]],
        ], JSON_THROW_ON_ERROR);

        $event = $this->gateway()->parseWebhook($payload, $this->sign($payload));

        $this->assertSame(WebhookEvent::PAYMENT_FAILED, $event->type);
    }

    /**
     * A refund carries NO session id — only a payment intent. Before the
     * fallback lookup existed this shape was silently discarded.
     */
    public function test_a_refund_carries_a_payment_id_but_no_session_id(): void
    {
        $payload = $this->chargeEvent('charge.refunded');

        $event = $this->gateway()->parseWebhook($payload, $this->sign($payload));

        $this->assertSame(WebhookEvent::PAYMENT_REFUNDED, $event->type);
        $this->assertNull($event->sessionId);
        $this->assertSame('pi_test_999', $event->paymentId);
    }

    /** A chargeback is its own thing — never silently treated as a refund. */
    public function test_a_dispute_maps_to_disputed_not_refunded(): void
    {
        $payload = $this->chargeEvent('charge.dispute.created');

        $event = $this->gateway()->parseWebhook($payload, $this->sign($payload));

        $this->assertSame(WebhookEvent::PAYMENT_DISPUTED, $event->type);
        $this->assertNotSame(WebhookEvent::PAYMENT_REFUNDED, $event->type);
        $this->assertSame('pi_test_999', $event->paymentId);
    }

    /** An event we do not model must pass through, not throw and not guess. */
    public function test_an_unmodelled_event_keeps_its_stripe_name(): void
    {
        $payload = json_encode([
            'id' => 'evt_unknown',
            'object' => 'event',
            'type' => 'payout.paid',
            'data' => ['object' => ['id' => 'po_test_1', 'object' => 'payout']],
        ], JSON_THROW_ON_ERROR);

        $event = $this->gateway()->parseWebhook($payload, $this->sign($payload));

        $this->assertSame('payout.paid', $event->type);
        $this->assertNull($event->sessionId);
    }

    /* ─────────────── the fallback lookup, end to end ─────────────── */

    /**
     * The whole point of carrying paymentId: a refund that names only a
     * payment intent must still find its purchase and reverse the tokens.
     */
    public function test_a_refund_keyed_only_by_payment_intent_reverses_the_tokens(): void
    {
        $user = $this->makeUser();
        $service = app(TokenPurchaseService::class);
        $ledger = app(TokenLedger::class);

        [$purchase] = $service->startPurchase($user, 'pro', 'EUR', 'https://x.test/ok', 'https://x.test/no');

        // Pay it, which is what records gateway_payment_id in the first place.
        $service->applyEvent(new WebhookEvent(
            id: 'evt_paid_1',
            type: WebhookEvent::PAYMENT_SUCCEEDED,
            sessionId: $purchase->fresh()->gateway_session_id,
            paymentId: 'pi_refund_target',
        ));

        $expected = (int) $purchase->tokens + (int) $purchase->bonus_tokens;
        $this->assertSame($expected, $ledger->balance($user));

        // Now refund it the way Stripe actually reports one: no session id.
        $service->applyEvent(new WebhookEvent(
            id: 'evt_refund_1',
            type: WebhookEvent::PAYMENT_REFUNDED,
            sessionId: null,
            paymentId: 'pi_refund_target',
        ));

        $this->assertSame(0, $ledger->balance($user));
        $this->assertSame(TokenPurchase::STATUS_REFUNDED, $purchase->fresh()->status);
    }

    /** A dispute records and escalates; it must not move a single token. */
    public function test_a_dispute_does_not_move_tokens(): void
    {
        $user = $this->makeUser();
        $service = app(TokenPurchaseService::class);
        $ledger = app(TokenLedger::class);

        [$purchase] = $service->startPurchase($user, 'pro', 'EUR', 'https://x.test/ok', 'https://x.test/no');

        $service->applyEvent(new WebhookEvent(
            id: 'evt_paid_2',
            type: WebhookEvent::PAYMENT_SUCCEEDED,
            sessionId: $purchase->fresh()->gateway_session_id,
            paymentId: 'pi_disputed',
        ));

        $before = $ledger->balance($user);
        $rowsBefore = TokenTransaction::count();

        $service->applyEvent(new WebhookEvent(
            id: 'evt_dispute_1',
            type: WebhookEvent::PAYMENT_DISPUTED,
            sessionId: null,
            paymentId: 'pi_disputed',
        ));

        $this->assertSame($before, $ledger->balance($user));
        $this->assertSame($rowsBefore, TokenTransaction::count());
    }

    /** An event naming nothing we know is ignored rather than misapplied. */
    public function test_an_event_matching_no_purchase_is_ignored(): void
    {
        $service = app(TokenPurchaseService::class);

        $this->assertNull($service->applyEvent(new WebhookEvent(
            id: 'evt_orphan',
            type: WebhookEvent::PAYMENT_REFUNDED,
            sessionId: null,
            paymentId: 'pi_never_seen',
        )));
    }
}
