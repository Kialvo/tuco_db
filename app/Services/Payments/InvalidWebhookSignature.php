<?php

namespace App\Services\Payments;

use RuntimeException;

class InvalidWebhookSignature extends RuntimeException
{
    public function __construct(string $message = 'Webhook signature verification failed.')
    {
        parent::__construct($message);
    }
}
