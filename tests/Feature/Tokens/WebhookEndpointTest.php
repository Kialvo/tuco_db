<?php

namespace Tests\Feature\Tokens;

use App\Models\PaymentEvent;
use App\Models\TokenTransaction;
use App\Services\Payments\FakeGateway;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\WebhookEvent;
use App\Services\Tokens\TokenLedger;
use App\Services\Tokens\TokenPurchaseService;

/**
 * The webhook endpoint itself, over HTTP.
 *
 * This is the only route in the application that credits money and accepts
 * unauthenticated, CSRF-free requests, so it gets tested as an endpoint rather
 * than only as a service.
 */
class WebhookEndpointTest extends TokenTestCase
{
    private function url(): string
    {
        return route('billing.webhook', ['gateway' => 'fake']);
    }

    private function gateway(): FakeGateway
    {
        return app(PaymentGateway::class);
    }

    private function startPurchase(): array
    {
        $user = $this->makeUser();
        [$purchase] = app(TokenPurchaseService::class)
            ->startPurchase($user, 'pro', 'EUR', 'https://x.test/ok', 'https://x.test/no');

        return [$user, $purchase];
    }

    public function test_a_signed_success_webhook_credits_the_account(): void
    {
        [$user, $purchase] = $this->startPurchase();

        [$payload, $headers] = $this->gateway()->makeSignedWebhook(
            WebhookEvent::PAYMENT_SUCCEEDED, $purchase->gateway_session_id
        );

        $this->call('POST', $this->url(), [], [], [], $this->serverHeaders($headers), $payload)
            ->assertOk()
            ->assertJson(['status' => 'ok']);

        $this->assertSame(1050, app(TokenLedger::class)->balance($user));
        $this->assertNotNull(PaymentEvent::first()->processed_at);
    }

    /** An endpoint that trusts its body is a free-tokens API. */
    public function test_an_unsigned_request_is_rejected_and_credits_nothing(): void
    {
        [$user, $purchase] = $this->startPurchase();

        $payload = json_encode([
            'id' => 'evt_forged',
            'type' => WebhookEvent::PAYMENT_SUCCEEDED,
            'session_id' => $purchase->gateway_session_id,
        ]);

        $this->call('POST', $this->url(), [], [], [], [], $payload)
            ->assertStatus(400);

        $this->assertSame(0, app(TokenLedger::class)->balance($user));
        $this->assertSame(0, TokenTransaction::count());
        $this->assertSame(0, PaymentEvent::count());
    }

    public function test_a_tampered_body_is_rejected(): void
    {
        [$victim, $victimPurchase] = $this->startPurchase();
        [, $attackerPurchase] = $this->startPurchase();

        // Sign one session, then swap in another: the signature no longer matches.
        [$payload, $headers] = $this->gateway()->makeSignedWebhook(
            WebhookEvent::PAYMENT_SUCCEEDED, $attackerPurchase->gateway_session_id
        );
        $tampered = str_replace(
            $attackerPurchase->gateway_session_id,
            $victimPurchase->gateway_session_id,
            $payload
        );

        $this->call('POST', $this->url(), [], [], [], $this->serverHeaders($headers), $tampered)
            ->assertStatus(400);

        $this->assertSame(0, app(TokenLedger::class)->balance($victim));
    }

    /** Gateways retry; the endpoint must be safe to hit repeatedly. */
    public function test_replaying_the_same_delivery_credits_once(): void
    {
        [$user, $purchase] = $this->startPurchase();

        [$payload, $headers] = $this->gateway()->makeSignedWebhook(
            WebhookEvent::PAYMENT_SUCCEEDED, $purchase->gateway_session_id
        );
        $server = $this->serverHeaders($headers);

        $this->call('POST', $this->url(), [], [], [], $server, $payload)->assertOk();
        $this->call('POST', $this->url(), [], [], [], $server, $payload)
            ->assertOk()
            ->assertJson(['status' => 'already processed']);

        $this->assertSame(1050, app(TokenLedger::class)->balance($user));
        $this->assertSame(1, PaymentEvent::count());
    }

    /** The route must not require a session or a CSRF token. */
    public function test_the_endpoint_is_reachable_without_authentication(): void
    {
        [, $purchase] = $this->startPurchase();

        [$payload, $headers] = $this->gateway()->makeSignedWebhook(
            WebhookEvent::PAYMENT_SUCCEEDED, $purchase->gateway_session_id
        );

        // No acting user, no token: a 419 or a redirect to login would mean the
        // gateway could never reach us in production.
        $response = $this->call('POST', $this->url(), [], [], [], $this->serverHeaders($headers), $payload);

        $this->assertSame(200, $response->getStatusCode());
    }

    /** Turn plain header names into the $_SERVER form the test client wants. */
    private function serverHeaders(array $headers): array
    {
        $server = [];

        foreach ($headers as $name => $value) {
            $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
        }

        return $server;
    }
}
