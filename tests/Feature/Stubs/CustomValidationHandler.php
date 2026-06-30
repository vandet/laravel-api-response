<?php

namespace Vandet\ApiResponse\Tests\Feature\Stubs;

use Illuminate\Http\JsonResponse;
use Vandet\ApiResponse\Contracts\ExceptionHandlerContract;

class CustomValidationHandler implements ExceptionHandlerContract
{
    public function handle(\Throwable $e): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => 'custom_validation_message',
            'code'    => 'CUSTOM_VALIDATION',
            'errors'  => (object) [],
        ], 422);
    }
}
