<?php

namespace Vandet\ApiResponse\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Vandet\ApiResponse\Constants\ErrorCodes;

class ResponseFactory
{
    public static function success(mixed $data, string $message): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], 200);
    }

    public static function created(mixed $data, string $message): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], 201);
    }

    public static function accepted(string $message): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'data'    => (object) [],
        ], 202);
    }

    public static function paginated(LengthAwarePaginator $paginator, string $message): JsonResponse
    {
        return new JsonResponse([
            'success'    => true,
            'message'    => $message,
            'data'       => array_values($paginator->items()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem() ?? 0,
                'to'           => $paginator->lastItem() ?? 0,
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last'  => $paginator->url($paginator->lastPage()),
                'next'  => $paginator->nextPageUrl(),
                'prev'  => $paginator->previousPageUrl(),
            ],
        ], 200);
    }

    public static function withIncluded(mixed $data, array $included, string $message): JsonResponse
    {
        return new JsonResponse([
            'success'  => true,
            'message'  => $message,
            'data'     => $data,
            'included' => $included,
        ], 200);
    }

    public static function deleted(): Response
    {
        return new Response(null, 204);
    }

    public static function validationError(array $errors, string $message = 'Validation failed.'): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'code'    => ErrorCodes::VALIDATION_FAILED,
            'errors'  => $errors,
        ], 422);
    }

    public static function notFound(string $code, string $message): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'code'    => $code,
            'errors'  => (object) [],
        ], 404);
    }

    public static function unauthorized(string $code, string $message): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'code'    => $code,
            'errors'  => (object) [],
        ], 401);
    }

    public static function forbidden(string $code, string $message): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'code'    => $code,
            'errors'  => (object) [],
        ], 403);
    }

    public static function conflict(string $code, string $message): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'code'    => $code,
            'errors'  => (object) [],
        ], 409);
    }

    public static function bulkPartialFailure(array $data, string $message = 'Some items failed.'): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'code'    => ErrorCodes::BULK_PARTIAL_FAILURE,
            'data'    => $data,
            'errors'  => (object) [],
        ], 207);
    }

    public static function error(string $code, string $message, int $status): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'code'    => $code,
            'errors'  => (object) [],
        ], $status);
    }

    public static function rateLimited(string $message = 'Too many requests. Please slow down.'): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'code'    => ErrorCodes::SERVER_RATE_LIMITED,
            'errors'  => (object) [],
        ], 429);
    }

    public static function serverError(string $message = 'An unexpected error occurred.'): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'code'    => ErrorCodes::SERVER_UNEXPECTED_ERROR,
            'errors'  => (object) [],
        ], 500);
    }
}
