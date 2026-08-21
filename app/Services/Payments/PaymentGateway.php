<?php

namespace App\Services\Payments;

use App\Models\TokenPurchase;

/**
 * The seam between the token system and whoever takes the money.
 *
 * Everything above this interface is gateway-agnostic, so Stripe can be added
 * later — and exercised locally through FakeGateway — without the ledger, the
 * controllers or the tests knowing which one is in play.
 */
interface PaymentGateway
{
    public function name(): string;

    /** Open a hosted checkout for a pending purchase. */
    public function createCheckoutSession(TokenPurchase $purchase, string $successUrl, string $cancelUrl): CheckoutSession;

    /**
     * Turn a raw webhook request into a verified event.
     *
     * Implementations MUST reject anything whose signature does not verify:
     * an endpoint that trusts its payload is a free-tokens API.
     *
     * @throws InvalidWebhookSignature
     */
    public function parseWebhook(string $payload, array $headers): WebhookEvent;
}
