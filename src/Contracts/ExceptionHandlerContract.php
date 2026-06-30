<?php

namespace Vandet\ApiResponse\Contracts;

use Illuminate\Http\JsonResponse;

interface ExceptionHandlerContract
{
    /**
     * Handle the exception and return a JSON response.
     * The exception type passed will always be the one declared in the exceptions config key.
     */
    public function handle(\Throwable $e): JsonResponse;
}
