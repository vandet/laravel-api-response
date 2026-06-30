<?php

namespace Vandet\ApiResponse\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Vandet\ApiResponse\Http\ResponseFactory;

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

    /**
     * Laravel calls this automatically for JSON requests — no renderable() registration needed.
     * Return false to fall through to Laravel's default rendering for non-JSON requests.
     */
    public function render(Request $request): JsonResponse|false
    {
        if (! $request->expectsJson()) {
            return false;
        }

        return ResponseFactory::error($this->errorCode, $this->getMessage(), $this->statusCode);
    }
}
