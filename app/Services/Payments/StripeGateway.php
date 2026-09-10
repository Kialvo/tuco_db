<?php

namespace App\Services\Payments;

use App\Models\TokenPurchase;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use UnexpectedValueException;

/**
 * Stripe, behind the same seam FakeGateway sits behind.
 *
 * Hosted Checkout, deliberately: the customer is redirected to Stripe's own
 * page, so card data never reaches this server (PCI scope stays SAQ-A), and
 * Apple Pay, Google Pay, Link and 3DS/SCA become dashboard toggles rather than
 * code we own.
 *
 * Two things here are load-bearing and easy to get wrong:
 *
 *  1. checkout.session.completed can arrive with payment_status other than
 *     'paid' — SEPA and other delayed methods complete the session first and
 *     settle days later. Treating that as success credits tokens for money
 *     that has not arrived.
 *
 *  2. Refunds and disputes are keyed by PAYMENT INTENT, not by session: the
 *     session id is absent from those payloads entirely. WebhookEvent carries
 *     both ids so TokenPurchaseService can fall back to the payment id.
 */
class StripeGateway implements PaymentGateway
{
    public const SIGNATURE_HEADER = 'stripe-signature';

    private ?StripeClient $client = null;

    public function __construct(
        private readonly string $secretKey,
        private readonly string $webhookSecret,
    ) {
        if ($this->secretKey === '') {
            throw new \RuntimeException(
                'STRIPE_SECRET_KEY is not set. Refusing to start the Stripe gateway with no credentials.'
            );
        }

        if ($this->webhookSecret === '') {
            throw new \RuntimeException(
                'STRIPE_WEBHOOK_SECRET is not set. Without it every webhook fails verification and no '
                .'purchase is ever credited. Locally: stripe listen --print-secret'
            );
        }
    }

    public function name(): string
    {
        return 'stripe';
    }

    /**
     * Open a hosted Checkout session for a pending purchase.
     *
     * The amount comes from the purchase, which took it from config — it never
     * originates in the browser. The idempotency key is derived from the
     * purchase id, so a double-clicked Buy button reuses the first session
     * instead of opening a second one against the same row.
     */
    public function createCheckoutSession(TokenPurchase $purchase, string $successUrl, string $cancelUrl): CheckoutSession
    {
        // Carried on BOTH the session and the payment intent on purpose: a
        // refund or dispute payload contains only the payment intent, so
        // metadata attached solely to the session would be unreachable exactly
        // when we most need to know which purchase is involved.
        $metadata = [
            'purchase_id' => (string) $purchase->id,
            'user_id' => (string) $purchase->user_id,
            'package_key' => (string) $purchase->package_key,
            'tokens' => (string) $purchase->tokens,
            'bonus_tokens' => (string) $purchase->bonus_tokens,
        ];

        $session = $this->client()->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower((string) $purchase->currency),
                    'unit_amount' => (int) $purchase->amount_minor,
                    'product_data' => [
                        'name' => $this->lineItemName($purchase),
                        'description' => '1 token = EUR 1, spendable on guest post placements.',
                    ],
                ],
            ]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,

            // Redundant with metadata, but it is the field Stripe surfaces in
            // the Dashboard, which is where a human debugs a stuck payment.
            'client_reference_id' => (string) $purchase->id,
            'customer_email' => $purchase->user?->email,
            'metadata' => $metadata,
            'payment_intent_data' => ['metadata' => $metadata],
        ], [
            'idempotency_key' => 'purchase:'.$purchase->id.':checkout',
        ]);

        return new CheckoutSession(
            id: (string) $session->id,
            url: (string) $session->url,
            amountMinor: (int) $purchase->amount_minor,
            currency: (string) $purchase->currency,
        );
    }

    /**
     * Verify a delivery and normalise it.
     *
     * Verification is not a formality: an endpoint that trusts its own request
     * body is a free-tokens API. Anything failing to verify is rejected before
     * a single field is read.
     */
    public function parseWebhook(string $payload, array $headers): WebhookEvent
    {
        $signature = $this->header($headers, self::SIGNATURE_HEADER);

        if ($signature === null || $signature === '') {
            throw new InvalidWebhookSignature('Missing Stripe-Signature header.');
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $this->webhookSecret);
        } catch (SignatureVerificationException $e) {
            throw new InvalidWebhookSignature('Stripe signature verification failed.');
        } catch (UnexpectedValueException $e) {
            throw new InvalidWebhookSignature('Malformed Stripe webhook payload.');
        }

        $object = $event->data->object ?? null;
        $stripeType = (string) $event->type;

        return new WebhookEvent(
            id: (string) $event->id,
            type: $this->mapType($stripeType, $object),
            sessionId: $this->sessionIdFrom($stripeType, $object),
            paymentId: $this->paymentIdFrom($stripeType, $object),
            payload: $event->toArray(),
        );
    }

    /**
     * Stripe's event vocabulary mapped onto ours.
     *
     * Anything unmapped keeps its Stripe name and falls through
     * TokenPurchaseService's match untouched, so an unfamiliar event becomes a
     * recorded no-op rather than an error or, worse, a guess.
     */
    private function mapType(string $stripeType, mixed $object): string
    {
        return match ($stripeType) {
            // Money is only real once payment_status is 'paid'. Delayed methods
            // complete the SESSION first and settle later, so this check is
            // what stands between us and crediting tokens for money that has
            // not arrived.
            'checkout.session.completed' => ($object->payment_status ?? null) === 'paid'
                ? WebhookEvent::PAYMENT_SUCCEEDED
                : 'checkout.session.completed.unpaid',

            'checkout.session.async_payment_succeeded' => WebhookEvent::PAYMENT_SUCCEEDED,

            'checkout.session.async_payment_failed',
            'checkout.session.expired',
            'payment_intent.payment_failed' => WebhookEvent::PAYMENT_FAILED,

            'charge.refunded' => WebhookEvent::PAYMENT_REFUNDED,

            // NOT a refund. The tokens may already be spent, and reversing them
            // automatically could drive a balance negative or strip credit from
            // a customer who did nothing wrong. A human decides.
            'charge.dispute.created' => WebhookEvent::PAYMENT_DISPUTED,

            default => $stripeType,
        };
    }

    /** Only session-shaped events carry one; refunds and disputes never do. */
    private function sessionIdFrom(string $stripeType, mixed $object): ?string
    {
        if (! str_starts_with($stripeType, 'checkout.session.')) {
            return null;
        }

        $id = $object->id ?? null;

        return $id === null ? null : (string) $id;
    }

    /**
     * The payment intent id, wherever this event keeps it. For a refund or a
     * dispute it is the only handle onto the original purchase.
     */
    private function paymentIdFrom(string $stripeType, mixed $object): ?string
    {
        if ($object === null) {
            return null;
        }

        $value = match (true) {
            str_starts_with($stripeType, 'checkout.session.') => $object->payment_intent ?? null,
            str_starts_with($stripeType, 'payment_intent.') => $object->id ?? null,
            // charge.* and charge.dispute.* both hang off a payment intent.
            default => $object->payment_intent ?? null,
        };

        if ($value === null) {
            return null;
        }

        // An expanded object arrives as a struct rather than a bare id string.
        if (is_object($value)) {
            return isset($value->id) ? (string) $value->id : null;
        }

        return (string) $value;
    }

    private function lineItemName(TokenPurchase $purchase): string
    {
        $bonus = (int) $purchase->bonus_tokens;
        $tokens = (int) $purchase->tokens;

        return $bonus > 0
            ? number_format($tokens).' tokens + '.number_format($bonus).' bonus'
            : number_format($tokens).' tokens';
    }

    private function client(): StripeClient
    {
        return $this->client ??= new StripeClient($this->secretKey);
    }

    /**
     * Header lookup that survives the shapes Symfony hands us: keys may be any
     * case, values may be a string or a single-element array.
     */
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
