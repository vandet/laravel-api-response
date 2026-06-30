<?php

namespace Vandet\ApiResponse\Tests\Feature;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Vandet\ApiResponse\Constants\ErrorCodes;
use Vandet\ApiResponse\Exceptions\ApiException;

class ExceptionHandlerIntegrationTest extends FeatureTestCase
{
    protected function defineRoutes($router): void
    {
        $router->get('/test/validation', fn () => throw ValidationException::withMessages(['email' => ['Required.']]));
        $router->get('/test/authentication', fn () => throw new AuthenticationException());
        $router->get('/test/authorization', fn () => throw new AuthorizationException());
        $router->get('/test/model-not-found', fn () => throw new ModelNotFoundException());
        $router->get('/test/route-not-found', fn () => throw new NotFoundHttpException());
        $router->get('/test/rate-limited', fn () => throw new TooManyRequestsHttpException());
        $router->get('/test/http-503', fn () => throw new HttpException(503, 'Down.'));
        $router->get('/test/server-error', fn () => throw new RuntimeException('Broken.'));
        $router->get('/test/api-exception', fn () => throw new ApiException(ErrorCodes::RESOURCE_CONFLICT, 'Conflict.', 409));
    }

    // ── Default handlers ──────────────────────────────────────────────────────

    public function test_validation_exception_returns_standard_envelope(): void
    {
        $this->getJson('/test/validation')
            ->assertStatus(422)
            ->assertJson(['success' => false, 'code' => ErrorCodes::VALIDATION_FAILED])
            ->assertJsonStructure(['success', 'message', 'code', 'errors']);
    }

    public function test_authentication_exception_returns_401(): void
    {
        $this->getJson('/test/authentication')
            ->assertStatus(401)
            ->assertJson(['success' => false, 'code' => ErrorCodes::AUTH_TOKEN_MISSING]);
    }

    public function test_authorization_exception_returns_403(): void
    {
        $this->getJson('/test/authorization')
            ->assertStatus(403)
            ->assertJson(['success' => false, 'code' => ErrorCodes::AUTH_USER_FORBIDDEN]);
    }

    public function test_model_not_found_exception_returns_404(): void
    {
        $this->getJson('/test/model-not-found')
            ->assertStatus(404)
            ->assertJson(['success' => false, 'code' => ErrorCodes::RESOURCE_NOT_FOUND]);
    }

    public function test_route_not_found_exception_returns_404(): void
    {
        $this->getJson('/test/route-not-found')
            ->assertStatus(404)
            ->assertJson(['success' => false, 'code' => ErrorCodes::RESOURCE_NOT_FOUND]);
    }

    public function test_rate_limited_exception_returns_429(): void
    {
        $this->getJson('/test/rate-limited')
            ->assertStatus(429)
            ->assertJson(['success' => false, 'code' => ErrorCodes::SERVER_RATE_LIMITED]);
    }

    public function test_http_503_returns_503_with_server_unavailable(): void
    {
        $this->getJson('/test/http-503')
            ->assertStatus(503)
            ->assertJson(['success' => false, 'code' => ErrorCodes::SERVER_UNAVAILABLE]);
    }

    public function test_unhandled_throwable_returns_500(): void
    {
        $this->getJson('/test/server-error')
            ->assertStatus(500)
            ->assertJson(['success' => false, 'code' => ErrorCodes::SERVER_UNEXPECTED_ERROR]);
    }

    public function test_api_exception_self_renders_with_its_code_and_status(): void
    {
        $this->getJson('/test/api-exception')
            ->assertStatus(409)
            ->assertJson(['success' => false, 'code' => ErrorCodes::RESOURCE_CONFLICT, 'message' => 'Conflict.']);
    }

    // ── Envelope shape ────────────────────────────────────────────────────────

    public function test_all_error_responses_contain_required_envelope_keys(): void
    {
        $endpoints = [
            '/test/validation',
            '/test/authentication',
            '/test/authorization',
            '/test/model-not-found',
            '/test/route-not-found',
            '/test/rate-limited',
            '/test/http-503',
            '/test/server-error',
        ];

        foreach ($endpoints as $endpoint) {
            $body = $this->getJson($endpoint)->json();
            $this->assertArrayHasKey('success', $body, "Missing 'success' on $endpoint");
            $this->assertArrayHasKey('message', $body, "Missing 'message' on $endpoint");
            $this->assertArrayHasKey('code', $body, "Missing 'code' on $endpoint");
            $this->assertArrayHasKey('errors', $body, "Missing 'errors' on $endpoint");
            $this->assertFalse($body['success'], "'success' should be false on $endpoint");
        }
    }
}
