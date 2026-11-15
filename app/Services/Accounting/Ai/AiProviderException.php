<?php

namespace App\Services\Accounting\Ai;

use RuntimeException;

class AiProviderException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode = 'provider_error',
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
