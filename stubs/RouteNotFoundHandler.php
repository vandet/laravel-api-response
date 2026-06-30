<?php

namespace App\Exceptions\Handlers;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Vandet\ApiResponse\Contracts\ExceptionHandlerContract;
use Vandet\ApiResponse\Exceptions\Handler;

class RouteNotFoundHandler implements ExceptionHandlerContract
{
    public function handle(\Throwable $e): JsonResponse
    {
        /** @var NotFoundHttpException $e */
        return Handler::handleNotFound($e);
    }
}
