<?php

namespace App\Exceptions\Handlers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Vandet\ApiResponse\Contracts\ExceptionHandlerContract;
use Vandet\ApiResponse\Exceptions\Handler;

class AuthorizationHandler implements ExceptionHandlerContract
{
    public function handle(\Throwable $e): JsonResponse
    {
        /** @var AuthorizationException $e */
        return Handler::handleAuthorization($e);
    }
}
