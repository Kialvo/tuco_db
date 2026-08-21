<?php

namespace App\Services\Payments;

/**
 * What a gateway hands back after a checkout is opened: where to send the
 * customer, and the id we will later match a webhook against.
 */
readonly class CheckoutSession
{
    public function __construct(
        public string $id,
        public string $url,
        public int $amountMinor,
        public string $currency,
    ) {}
}
