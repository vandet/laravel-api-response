<?php

namespace App\Exceptions\Handlers;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Vandet\ApiResponse\Contracts\ExceptionHandlerContract;
use Vandet\ApiResponse\Exceptions\Handler;

class AuthenticationHandler implements ExceptionHandlerContract
{
    public function handle(\Throwable $e): JsonResponse
    {
        /** @var AuthenticationException $e */
        return Handler::handleAuthentication($e);
    }
}
