<?php

namespace App\Exceptions\Handlers;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Vandet\ApiResponse\Contracts\ExceptionHandlerContract;
use Vandet\ApiResponse\Exceptions\Handler;

class RateLimitedHandler implements ExceptionHandlerContract
{
    public function handle(\Throwable $e): JsonResponse
    {
        /** @var TooManyRequestsHttpException $e */
        return Handler::handleRateLimited($e);
    }
}
