<?php

namespace App\Exceptions\Handlers;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Vandet\ApiResponse\Contracts\ExceptionHandlerContract;
use Vandet\ApiResponse\Exceptions\Handler;

class ValidationHandler implements ExceptionHandlerContract
{
    public function handle(\Throwable $e): JsonResponse
    {
        /** @var ValidationException $e */
        return Handler::handleValidation($e);
    }
}
