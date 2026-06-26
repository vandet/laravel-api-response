<?php

namespace Vandet\ApiResponse\Exceptions;

use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Vandet\ApiResponse\Constants\ErrorCodes;
use Vandet\ApiResponse\Http\ResponseFactory;

class Handler
{
    public static function handleValidation(ValidationException $e): JsonResponse
    {
        return ResponseFactory::validationError($e->errors());
    }

    public static function handleAuthentication(AuthenticationException $e): JsonResponse
    {
        return ResponseFactory::unauthorized(
            ErrorCodes::AUTH_TOKEN_MISSING,
            'Unauthenticated.'
        );
    }

    public static function handleAuthorization(AuthorizationException $e): JsonResponse
    {
        return ResponseFactory::forbidden(
            ErrorCodes::AUTH_USER_FORBIDDEN,
            'You do not have permission to perform this action.'
        );
    }

    public static function handleModelNotFound(ModelNotFoundException $e): JsonResponse
    {
        return ResponseFactory::notFound(
            ErrorCodes::RESOURCE_NOT_FOUND,
            'Resource not found.'
        );
    }

    public static function handleNotFound(NotFoundHttpException $e): JsonResponse
    {
        return ResponseFactory::notFound(
            ErrorCodes::RESOURCE_NOT_FOUND,
            'The requested resource was not found.'
        );
    }

    public static function handleRateLimited(TooManyRequestsHttpException $e): JsonResponse
    {
        return ResponseFactory::rateLimited();
    }

    public static function handleServerError(Throwable $e): JsonResponse
    {
        return ResponseFactory::serverError();
    }
}
