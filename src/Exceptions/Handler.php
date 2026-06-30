<?php

namespace Vandet\ApiResponse\Exceptions;

use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Vandet\ApiResponse\Constants\ErrorCodes;
use Vandet\ApiResponse\Http\ResponseFactory;

class Handler
{
    public static function handleApiException(ApiException $e): JsonResponse
    {
        return ResponseFactory::error($e->getErrorCode(), $e->getMessage(), $e->getStatusCode());
    }

    public static function handleValidation(ValidationException $e): JsonResponse
    {
        return ResponseFactory::validationError($e->errors());
    }

    public static function handleAuthentication(AuthenticationException $e): JsonResponse
    {
        return ResponseFactory::unauthorized(
            ErrorCodes::AUTH_TOKEN_MISSING,
            trans('api-response::messages.unauthenticated'),
        );
    }

    public static function handleAuthorization(AuthorizationException $e): JsonResponse
    {
        return ResponseFactory::forbidden(
            ErrorCodes::AUTH_USER_FORBIDDEN,
            trans('api-response::messages.forbidden'),
        );
    }

    public static function handleModelNotFound(ModelNotFoundException $e): JsonResponse
    {
        return ResponseFactory::notFound(
            ErrorCodes::RESOURCE_NOT_FOUND,
            trans('api-response::messages.not_found'),
        );
    }

    public static function handleNotFound(NotFoundHttpException $e): JsonResponse
    {
        return ResponseFactory::notFound(
            ErrorCodes::RESOURCE_NOT_FOUND,
            trans('api-response::messages.route_not_found'),
        );
    }

    public static function handleRateLimited(TooManyRequestsHttpException $e): JsonResponse
    {
        return ResponseFactory::rateLimited();
    }

    public static function handleHttpError(HttpException $e): JsonResponse
    {
        $message = $e->getMessage();

        return match ($e->getStatusCode()) {
            503 => ResponseFactory::error(
                ErrorCodes::SERVER_UNAVAILABLE,
                $message ?: trans('api-response::messages.service_unavailable'),
                503
            ),
            default => ResponseFactory::error(
                ErrorCodes::SERVER_UNEXPECTED_ERROR,
                $message ?: trans('api-response::messages.server_error'),
                $e->getStatusCode()
            ),
        };
    }

    public static function handleServerError(Throwable $e): JsonResponse
    {
        $message = config('app.debug')
            ? ($e->getMessage() ?: trans('api-response::messages.server_error'))
            : trans('api-response::messages.server_error');

        return ResponseFactory::serverError($message);
    }
}
