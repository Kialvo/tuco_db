<?php

namespace App\Services\Payments;

/**
 * A gateway event, normalised. `sessionId` is what ties it back to a
 * TokenPurchase; `id` is what makes processing idempotent.
 */
readonly class WebhookEvent
{
    public const PAYMENT_SUCCEEDED = 'payment.succeeded';

    public const PAYMENT_FAILED = 'payment.failed';

    public const PAYMENT_REFUNDED = 'payment.refunded';

    /**
     * A chargeback. Deliberately NOT a refund: the tokens may already be spent,
     * so reversing them automatically could overdraw an account or strip credit
     * from a customer who did nothing wrong. Recorded and escalated to a human.
     */
    public const PAYMENT_DISPUTED = 'payment.disputed';

    public function __construct(
        public string $id,
        public string $type,
        public ?string $sessionId,
        public ?string $paymentId,
        public array $payload = [],
    ) {}
}
