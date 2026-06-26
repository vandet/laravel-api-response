<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Vandet\ApiResponse\Constants\ErrorCodes;
use Vandet\ApiResponse\Http\ResponseFactory;

// ── Helpers ─────────────────────────────────────────────────────────────────

function decode(JsonResponse $response): array
{
    return json_decode($response->getContent(), true);
}

function decodeRaw(JsonResponse $response): object
{
    return json_decode($response->getContent());
}

// ── Success responses ────────────────────────────────────────────────────────

it('returns 200 with data and message for success()', function () {
    $response = ResponseFactory::success(['id' => 1, 'name' => 'Vandet'], 'User retrieved successfully.');

    expect($response->getStatusCode())->toBe(200);

    $body = decode($response);
    expect($body['success'])->toBeTrue();
    expect($body['message'])->toBe('User retrieved successfully.');
    expect($body['data'])->toBe(['id' => 1, 'name' => 'Vandet']);
});

it('success() returns empty array for empty collection', function () {
    $response = ResponseFactory::success([], 'No users found.');

    $body = decode($response);
    expect($body['data'])->toBe([]);
});

it('returns 201 for created()', function () {
    $response = ResponseFactory::created(['id' => 1], 'User created successfully.');

    expect($response->getStatusCode())->toBe(201);

    $body = decode($response);
    expect($body['success'])->toBeTrue();
    expect($body['data'])->toBe(['id' => 1]);
});

it('returns 202 for accepted()', function () {
    $response = ResponseFactory::accepted('Import queued successfully.');

    expect($response->getStatusCode())->toBe(202);

    $body = decode($response);
    expect($body['success'])->toBeTrue();
    expect($body['message'])->toBe('Import queued successfully.');
});

it('returns 200 with pagination and links for paginated()', function () {
    $items     = [['id' => 1], ['id' => 2]];
    $paginator = new LengthAwarePaginator($items, total: 50, perPage: 20, currentPage: 2);

    $response = ResponseFactory::paginated($paginator, 'Users retrieved successfully.');

    expect($response->getStatusCode())->toBe(200);

    $body = decode($response);
    expect($body['success'])->toBeTrue();
    expect($body['data'])->toBe($items);
    expect($body['pagination']['current_page'])->toBe(2);
    expect($body['pagination']['last_page'])->toBe(3);
    expect($body['pagination']['per_page'])->toBe(20);
    expect($body['pagination']['total'])->toBe(50);
    expect($body['pagination']['from'])->toBe(21);
    expect($body['pagination']['to'])->toBe(40);
    expect($body)->toHaveKey('links');
    expect($body['links'])->toHaveKeys(['first', 'last', 'next', 'prev']);
});

it('paginated() sets prev to null on first page', function () {
    $paginator = new LengthAwarePaginator([['id' => 1]], total: 5, perPage: 20, currentPage: 1);

    $body = decode(ResponseFactory::paginated($paginator, 'Users retrieved successfully.'));

    expect($body['links']['prev'])->toBeNull();
});

it('paginated() sets next to null on last page', function () {
    $paginator = new LengthAwarePaginator([['id' => 1]], total: 5, perPage: 20, currentPage: 1);

    $body = decode(ResponseFactory::paginated($paginator, 'Users retrieved successfully.'));

    expect($body['links']['next'])->toBeNull();
});

it('returns 200 with included for withIncluded()', function () {
    $data     = [['id' => 1, 'role_id' => 2]];
    $included = ['roles' => [['id' => 2, 'name' => 'Editor']]];

    $response = ResponseFactory::withIncluded($data, $included, 'Users retrieved successfully.');

    expect($response->getStatusCode())->toBe(200);

    $body = decode($response);
    expect($body['success'])->toBeTrue();
    expect($body['data'])->toBe($data);
    expect($body['included'])->toBe($included);
});

it('returns 204 with no body for deleted()', function () {
    $response = ResponseFactory::deleted();

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(204);
    expect($response->getContent())->toBe('');
});

// ── Error responses ──────────────────────────────────────────────────────────

it('returns 422 with field errors for validationError()', function () {
    $errors   = ['email' => ['Email is required.'], 'name' => ['Name is required.']];
    $response = ResponseFactory::validationError($errors);

    expect($response->getStatusCode())->toBe(422);

    $body = decode($response);
    expect($body['success'])->toBeFalse();
    expect($body['code'])->toBe(ErrorCodes::VALIDATION_FAILED);
    expect($body['errors'])->toBe($errors);
    expect($body)->not->toHaveKey('data');
});

it('returns 404 for notFound()', function () {
    $response = ResponseFactory::notFound(ErrorCodes::USER_NOT_FOUND, 'User not found.');

    expect($response->getStatusCode())->toBe(404);

    $body    = decode($response);
    $bodyRaw = decodeRaw($response);
    expect($body['success'])->toBeFalse();
    expect($body['code'])->toBe(ErrorCodes::USER_NOT_FOUND);
    expect($body)->not->toHaveKey('data');
    expect($bodyRaw->errors)->toBeInstanceOf(stdClass::class); // {} not []
});

it('returns 401 for unauthorized()', function () {
    $response = ResponseFactory::unauthorized(ErrorCodes::AUTH_TOKEN_EXPIRED, 'Token has expired.');

    expect($response->getStatusCode())->toBe(401);

    $body = decode($response);
    expect($body['success'])->toBeFalse();
    expect($body['code'])->toBe(ErrorCodes::AUTH_TOKEN_EXPIRED);
    expect($body)->not->toHaveKey('data');
});

it('returns 403 for forbidden()', function () {
    $response = ResponseFactory::forbidden(ErrorCodes::AUTH_USER_FORBIDDEN, 'Permission denied.');

    expect($response->getStatusCode())->toBe(403);

    $body = decode($response);
    expect($body['success'])->toBeFalse();
    expect($body['code'])->toBe(ErrorCodes::AUTH_USER_FORBIDDEN);
});

it('returns 409 for conflict()', function () {
    $response = ResponseFactory::conflict(ErrorCodes::USER_EMAIL_DUPLICATE, 'Email already registered.');

    expect($response->getStatusCode())->toBe(409);

    $body = decode($response);
    expect($body['success'])->toBeFalse();
    expect($body['code'])->toBe(ErrorCodes::USER_EMAIL_DUPLICATE);
});

it('returns 429 for rateLimited()', function () {
    $response = ResponseFactory::rateLimited();

    expect($response->getStatusCode())->toBe(429);

    $body = decode($response);
    expect($body['success'])->toBeFalse();
    expect($body['code'])->toBe(ErrorCodes::SERVER_RATE_LIMITED);
});

it('returns 500 for serverError()', function () {
    $response = ResponseFactory::serverError();

    expect($response->getStatusCode())->toBe(500);

    $body = decode($response);
    expect($body['success'])->toBeFalse();
    expect($body['code'])->toBe(ErrorCodes::SERVER_UNEXPECTED_ERROR);
});

// ── 207 bulk partial failure (special case) ──────────────────────────────────

it('returns 207 with data for bulkPartialFailure()', function () {
    $data = [
        'created' => 1,
        'failed'  => 1,
        'items'   => [
            ['index' => 0, 'success' => true,  'id' => '550e8400'],
            ['index' => 1, 'success' => false, 'code' => 'USER_EMAIL_DUPLICATE'],
        ],
    ];

    $response = ResponseFactory::bulkPartialFailure($data);

    expect($response->getStatusCode())->toBe(207);

    $body = decode($response);
    expect($body['success'])->toBeFalse();
    expect($body['code'])->toBe(ErrorCodes::BULK_PARTIAL_FAILURE);
    expect($body)->toHaveKey('data'); // only error response that includes data
    expect($body['data'])->toBe($data);
});

// ── Envelope integrity ───────────────────────────────────────────────────────

it('never includes data on error responses except 207', function () {
    $errorResponses = [
        ResponseFactory::validationError(['field' => ['error']]),
        ResponseFactory::notFound(ErrorCodes::RESOURCE_NOT_FOUND, 'Not found.'),
        ResponseFactory::unauthorized(ErrorCodes::AUTH_TOKEN_MISSING, 'Unauthenticated.'),
        ResponseFactory::forbidden(ErrorCodes::AUTH_USER_FORBIDDEN, 'Forbidden.'),
        ResponseFactory::conflict(ErrorCodes::RESOURCE_CONFLICT, 'Conflict.'),
        ResponseFactory::rateLimited(),
        ResponseFactory::serverError(),
    ];

    foreach ($errorResponses as $response) {
        $body = decode($response);
        expect($body)->not->toHaveKey('data');
    }
});

it('never includes optional envelope fields when absent', function () {
    $response = ResponseFactory::success(['id' => 1], 'OK.');
    $body     = decode($response);

    expect($body)->not->toHaveKey('pagination');
    expect($body)->not->toHaveKey('links');
    expect($body)->not->toHaveKey('included');
    expect($body)->not->toHaveKey('meta');
});

it('all responses include message', function () {
    $responses = [
        ResponseFactory::success([], 'OK.'),
        ResponseFactory::created(['id' => 1], 'Created.'),
        ResponseFactory::accepted('Accepted.'),
        ResponseFactory::validationError(['f' => ['e']]),
        ResponseFactory::notFound(ErrorCodes::RESOURCE_NOT_FOUND, 'Not found.'),
        ResponseFactory::serverError(),
    ];

    foreach ($responses as $response) {
        $body = decode($response);
        expect($body)->toHaveKey('message');
        expect($body['message'])->not->toBeEmpty();
    }
});
