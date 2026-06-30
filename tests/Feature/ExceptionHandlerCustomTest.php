<?php

namespace Vandet\ApiResponse\Tests\Feature;

use Illuminate\Validation\ValidationException;
use Vandet\ApiResponse\Tests\Feature\Stubs\CustomValidationHandler;

/**
 * Tests config key set to a custom class — the class is resolved from the container and called.
 * defineEnvironment() runs before ServiceProvider::boot(), so the class string is wired at registration time.
 */
class ExceptionHandlerCustomTest extends FeatureTestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('api-response.exceptions.validation', CustomValidationHandler::class);
    }

    protected function defineRoutes($router): void
    {
        $router->get('/test/validation', fn () => throw ValidationException::withMessages(['email' => ['Required.']]));
    }

    public function test_custom_handler_class_is_resolved_and_called(): void
    {
        $this->getJson('/test/validation')
            ->assertStatus(422)
            ->assertJson([
                'code'    => 'CUSTOM_VALIDATION',
                'message' => 'custom_validation_message',
            ]);
    }
}
