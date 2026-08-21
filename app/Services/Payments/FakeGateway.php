<?php

namespace App\Services\Payments;

use App\Models\TokenPurchase;
use Illuminate\Support\Str;

/**
 * A gateway that takes no money and talks to nobody.
 *
 * Purpose: build and exercise the whole top-up flow — checkout, webhook,
 * crediting, refunds — with no Stripe account, no API keys and no network.
 * It is the default driver precisely so that a missing PAYMENTS_DRIVER can
 * never put a half-configured live gateway in front of a customer.
 *
 * It still enforces a signature, using an HMAC over the payload with a local
 * secret. That is not security theatre: it means the webhook controller's
 * verification path is exercised locally rather than only in production, where
 * getting it wrong means giving away tokens.
 */
class FakeGateway implements PaymentGateway
{
    /**
     * Local-only signing secret. Not a credential: it protects nothing real,
     * it exists so the verify-then-process path is the same shape as Stripe's.
     */
    public const LOCAL_SECRET = 'fake-gateway-local-secret';

    public const SIGNATURE_HEADER = 'x-fake-signature';

    public function name(): string
    {
        return 'fake';
    }

    public function createCheckoutSession(TokenPurchase $purchase, string $successUrl, string $cancelUrl): CheckoutSession
    {
        $id = 'fake_cs_'.Str::lower(Str::random(24));

        // A local page stands in for the hosted checkout, offering "pay" and
        // "fail" buttons that post a signed webhook back to us. It does not
        // exist until the HTTP phase, so fall back to an inert URL rather than
        // making the whole service unusable without routes.
        $url = \Illuminate\Support\Facades\Route::has('billing.fake-checkout')
            ? route('billing.fake-checkout', ['session' => $id])
            : 'https://fake-gateway.test/checkout/'.$id;

        return new CheckoutSession(
            id: $id,
            url: $url,
            amountMinor: (int) $purchase->amount_minor,
            currency: (string) $purchase->currency,
        );
    }

    public function parseWebhook(string $payload, array $headers): WebhookEvent
    {
        $provided = $this->header($headers, self::SIGNATURE_HEADER);

        if ($provided === null || ! hash_equals($this->sign($payload), $provided)) {
            throw new InvalidWebhookSignature;
        }

        $data = json_decode($payload, true);

        if (! is_array($data) || ! isset($data['id'], $data['type'])) {
            throw new InvalidWebhookSignature('Malformed webhook payload.');
        }

        return new WebhookEvent(
            id: (string) $data['id'],
            type: (string) $data['type'],
            sessionId: isset($data['session_id']) ? (string) $data['session_id'] : null,
            paymentId: isset($data['payment_id']) ? (string) $data['payment_id'] : null,
            payload: $data,
        );
    }

    /** Sign a payload the way this gateway expects to receive it. */
    public function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, self::LOCAL_SECRET);
    }

    /**
     * Build a signed webhook body — used by the local checkout stand-in and by
     * tests to simulate the gateway calling us back.
     *
     * @return array{0: string, 1: array<string,string>} [payload, headers]
     */
    public function makeSignedWebhook(string $type, string $sessionId, ?string $eventId = null): array
    {
        $payload = json_encode([
            'id' => $eventId ?? 'fake_evt_'.Str::lower(Str::random(20)),
            'type' => $type,
            'session_id' => $sessionId,
            'payment_id' => 'fake_pi_'.Str::lower(Str::random(20)),
        ], JSON_THROW_ON_ERROR);

        return [$payload, [self::SIGNATURE_HEADER => $this->sign($payload)]];
    }

    private function header(array $headers, string $name): ?string
    {
        foreach ($headers as $key => $value) {
            if (strtolower((string) $key) === $name) {
                return is_array($value) ? (string) ($value[0] ?? '') : (string) $value;
            }
        }

        return null;
    }
}
