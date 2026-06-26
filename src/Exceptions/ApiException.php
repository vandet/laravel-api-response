<?php

namespace Vandet\ApiResponse\Exceptions;

use RuntimeException;

class ApiException extends RuntimeException
{
    public function __construct(
        private readonly string $errorCode,
        string $message,
        private readonly int $statusCode = 500,
    ) {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
