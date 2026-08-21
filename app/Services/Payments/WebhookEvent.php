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

    public function __construct(
        public string $id,
        public string $type,
        public ?string $sessionId,
        public ?string $paymentId,
        public array $payload = [],
    ) {}
}
