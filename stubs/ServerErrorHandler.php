<?php

namespace App\Exceptions\Handlers;

use Illuminate\Http\JsonResponse;
use Vandet\ApiResponse\Contracts\ExceptionHandlerContract;
use Vandet\ApiResponse\Exceptions\Handler;

class ServerErrorHandler implements ExceptionHandlerContract
{
    public function handle(\Throwable $e): JsonResponse
    {
        return Handler::handleServerError($e);
    }
}
