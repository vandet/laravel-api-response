<?php

namespace Vandet\ApiResponse\Tests\Unit;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use stdClass;
use Vandet\ApiResponse\Constants\ErrorCodes;
use Vandet\ApiResponse\Http\ResponseFactory;
use Vandet\ApiResponse\Tests\TestCase;

class ResponseFactoryTest extends TestCase
{
    private function decode(JsonResponse $response): array
    {
        return json_decode($response->getContent(), true);
    }

    private function decodeRaw(JsonResponse $response): object
    {
        return json_decode($response->getContent());
    }

    // ── Success responses ─────────────────────────────────────────────────────

    public function test_success_returns_200_with_data_and_message(): void
    {
        $response = ResponseFactory::success(['id' => 1, 'name' => 'Vandet'], 'User retrieved successfully.');

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertTrue($body['success']);
        $this->assertSame('User retrieved successfully.', $body['message']);
        $this->assertSame(['id' => 1, 'name' => 'Vandet'], $body['data']);
    }

    public function test_success_returns_empty_array_for_empty_collection(): void
    {
        $body = $this->decode(ResponseFactory::success([], 'No users found.'));

        $this->assertSame([], $body['data']);
    }

    public function test_created_returns_201(): void
    {
        $response = ResponseFactory::created(['id' => 1], 'User created successfully.');

        $this->assertSame(201, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertTrue($body['success']);
        $this->assertSame(['id' => 1], $body['data']);
    }

    public function test_accepted_returns_202(): void
    {
        $response = ResponseFactory::accepted('Import queued successfully.');

        $this->assertSame(202, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertTrue($body['success']);
        $this->assertSame('Import queued successfully.', $body['message']);
    }

    public function test_paginated_returns_200_with_pagination_and_links(): void
    {
        $items     = array_map(fn ($i) => ['id' => $i], range(1, 20));
        $paginator = new LengthAwarePaginator($items, total: 50, perPage: 20, currentPage: 2);

        $response = ResponseFactory::paginated($paginator, 'Users retrieved successfully.');

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertTrue($body['success']);
        $this->assertSame($items, $body['data']);
        $this->assertSame(2, $body['pagination']['current_page']);
        $this->assertSame(3, $body['pagination']['last_page']);
        $this->assertSame(20, $body['pagination']['per_page']);
        $this->assertSame(50, $body['pagination']['total']);
        $this->assertSame(21, $body['pagination']['from']);
        $this->assertSame(40, $body['pagination']['to']);
        $this->assertArrayHasKey('links', $body);
        foreach (['first', 'last', 'next', 'prev'] as $key) {
            $this->assertArrayHasKey($key, $body['links']);
        }
    }

    public function test_paginated_sets_prev_to_null_on_first_page(): void
    {
        $paginator = new LengthAwarePaginator([['id' => 1]], total: 5, perPage: 20, currentPage: 1);

        $body = $this->decode(ResponseFactory::paginated($paginator, 'Users retrieved successfully.'));

        $this->assertNull($body['links']['prev']);
    }

    public function test_paginated_sets_next_to_null_on_last_page(): void
    {
        $paginator = new LengthAwarePaginator([['id' => 1]], total: 5, perPage: 20, currentPage: 1);

        $body = $this->decode(ResponseFactory::paginated($paginator, 'Users retrieved successfully.'));

        $this->assertNull($body['links']['next']);
    }

    public function test_with_included_returns_200_with_included(): void
    {
        $data     = [['id' => 1, 'role_id' => 2]];
        $included = ['roles' => [['id' => 2, 'name' => 'Editor']]];

        $response = ResponseFactory::withIncluded($data, $included, 'Users retrieved successfully.');

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertTrue($body['success']);
        $this->assertSame($data, $body['data']);
        $this->assertSame($included, $body['included']);
    }

    public function test_deleted_returns_204_with_no_body(): void
    {
        $response = ResponseFactory::deleted();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    // ── Error responses ───────────────────────────────────────────────────────

    public function test_validation_error_returns_422_with_field_errors(): void
    {
        $errors   = ['email' => ['Email is required.'], 'name' => ['Name is required.']];
        $response = ResponseFactory::validationError($errors);

        $this->assertSame(422, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::VALIDATION_FAILED, $body['code']);
        $this->assertSame($errors, $body['errors']);
        $this->assertArrayNotHasKey('data', $body);
    }

    public function test_not_found_returns_404(): void
    {
        $response = ResponseFactory::notFound(ErrorCodes::USER_NOT_FOUND, 'User not found.');

        $this->assertSame(404, $response->getStatusCode());
        $body    = $this->decode($response);
        $bodyRaw = $this->decodeRaw($response);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::USER_NOT_FOUND, $body['code']);
        $this->assertArrayNotHasKey('data', $body);
        $this->assertInstanceOf(stdClass::class, $bodyRaw->errors);
    }

    public function test_unauthorized_returns_401(): void
    {
        $response = ResponseFactory::unauthorized(ErrorCodes::AUTH_TOKEN_EXPIRED, 'Token has expired.');

        $this->assertSame(401, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::AUTH_TOKEN_EXPIRED, $body['code']);
        $this->assertArrayNotHasKey('data', $body);
    }

    public function test_forbidden_returns_403(): void
    {
        $response = ResponseFactory::forbidden(ErrorCodes::AUTH_USER_FORBIDDEN, 'Permission denied.');

        $this->assertSame(403, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::AUTH_USER_FORBIDDEN, $body['code']);
    }

    public function test_conflict_returns_409(): void
    {
        $response = ResponseFactory::conflict(ErrorCodes::USER_EMAIL_DUPLICATE, 'Email already registered.');

        $this->assertSame(409, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::USER_EMAIL_DUPLICATE, $body['code']);
    }

    public function test_rate_limited_returns_429(): void
    {
        $response = ResponseFactory::rateLimited();

        $this->assertSame(429, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::SERVER_RATE_LIMITED, $body['code']);
    }

    public function test_server_error_returns_500(): void
    {
        $response = ResponseFactory::serverError();

        $this->assertSame(500, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::SERVER_UNEXPECTED_ERROR, $body['code']);
    }

    // ── 207 bulk partial failure ──────────────────────────────────────────────

    public function test_bulk_partial_failure_returns_207_with_data(): void
    {
        $data = [
            'created' => 1,
            'failed'  => 1,
            'items'   => [
                ['index' => 0, 'success' => true,  'id' => '550e8400'],
                ['index' => 1, 'success' => false, 'code' => 'USER_EMAIL_DUPLICATE'],
            ],
        ];

        $response = ResponseFactory::bulkPartialFailure($data);

        $this->assertSame(207, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::BULK_PARTIAL_FAILURE, $body['code']);
        $this->assertArrayHasKey('data', $body);
        $this->assertSame($data, $body['data']);
    }

    // ── Envelope integrity ────────────────────────────────────────────────────

    public function test_error_responses_never_include_data_except_207(): void
    {
        $responses = [
            ResponseFactory::validationError(['field' => ['error']]),
            ResponseFactory::notFound(ErrorCodes::RESOURCE_NOT_FOUND, 'Not found.'),
            ResponseFactory::unauthorized(ErrorCodes::AUTH_TOKEN_MISSING, 'Unauthenticated.'),
            ResponseFactory::forbidden(ErrorCodes::AUTH_USER_FORBIDDEN, 'Forbidden.'),
            ResponseFactory::conflict(ErrorCodes::RESOURCE_CONFLICT, 'Conflict.'),
            ResponseFactory::rateLimited(),
            ResponseFactory::serverError(),
        ];

        foreach ($responses as $response) {
            $this->assertArrayNotHasKey('data', $this->decode($response));
        }
    }

    public function test_optional_envelope_fields_are_absent_when_not_used(): void
    {
        $body = $this->decode(ResponseFactory::success(['id' => 1], 'OK.'));

        $this->assertArrayNotHasKey('pagination', $body);
        $this->assertArrayNotHasKey('links', $body);
        $this->assertArrayNotHasKey('included', $body);
        $this->assertArrayNotHasKey('meta', $body);
    }

    public function test_all_responses_include_message(): void
    {
        $responses = [
            ResponseFactory::success([], 'OK.'),
            ResponseFactory::created(['id' => 1], 'Created.'),
            ResponseFactory::accepted('Accepted.'),
            ResponseFactory::validationError(['f' => ['e']]),
            ResponseFactory::notFound(ErrorCodes::RESOURCE_NOT_FOUND, 'Not found.'),
            ResponseFactory::serverError(),
        ];

        foreach ($responses as $response) {
            $body = $this->decode($response);
            $this->assertArrayHasKey('message', $body);
            $this->assertNotEmpty($body['message']);
        }
    }
}
