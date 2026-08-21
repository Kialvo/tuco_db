<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientTokens extends RuntimeException
{
    public function __construct(public readonly int $balance, public readonly int $required)
    {
        parent::__construct("Insufficient tokens: balance {$balance}, required {$required}.");
    }

    public function shortfall(): int
    {
        return max(0, $this->required - $this->balance);
    }
}
