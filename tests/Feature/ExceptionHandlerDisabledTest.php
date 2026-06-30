<?php

namespace Vandet\ApiResponse\Tests\Feature;

use Illuminate\Validation\ValidationException;
use RuntimeException;
use Vandet\ApiResponse\Constants\ErrorCodes;

/**
 * Tests config key set to false — handler is not registered, Laravel uses its default rendering.
 * defineEnvironment() runs before ServiceProvider::boot(), so the config is read at registration time.
 */
class ExceptionHandlerDisabledTest extends FeatureTestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('api-response.exceptions.validation', false);
        $app['config']->set('api-response.exceptions.server_error', false);
    }

    protected function defineRoutes($router): void
    {
        $router->get('/test/validation', fn () => throw ValidationException::withMessages(['email' => ['Required.']]));
        $router->get('/test/server-error', fn () => throw new RuntimeException('Broken.'));
    }

    public function test_disabled_validation_handler_does_not_use_package_envelope(): void
    {
        $response = $this->getJson('/test/validation');

        $response->assertStatus(422);
        // Laravel's default validation response has no 'code' field
        $this->assertArrayNotHasKey('code', $response->json());
    }

    public function test_disabled_server_error_handler_does_not_use_package_envelope(): void
    {
        $response = $this->getJson('/test/server-error');

        $response->assertStatus(500);
        // Laravel's default 500 response has no 'code' field
        $this->assertArrayNotHasKey('code', $response->json());
    }
}
