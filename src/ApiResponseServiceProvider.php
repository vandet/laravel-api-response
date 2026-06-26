<?php

namespace Vandet\ApiResponse;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Vandet\ApiResponse\Exceptions\ApiException;
use Vandet\ApiResponse\Exceptions\Handler;
use Vandet\ApiResponse\Http\ResponseFactory;

class ApiResponseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/api-response.php', 'api-response');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/api-response.php' => config_path('api-response.php'),
            ], 'api-response-config');
        }

        if (! config('api-response.handle_exceptions', true)) {
            return;
        }

        $exceptions = config('api-response.exceptions', []);
        $handler    = $this->app->make(ExceptionHandler::class);

        // Always intercept ApiException — it already carries its own code and status
        $handler->renderable(function (ApiException $e, $request) {
            if ($request->expectsJson()) {
                return ResponseFactory::error($e->getErrorCode(), $e->getMessage(), $e->getStatusCode());
            }
        });

        if ($exceptions['validation'] ?? true) {
            $handler->renderable(function (ValidationException $e, $request) {
                if ($request->expectsJson()) {
                    return Handler::handleValidation($e);
                }
            });
        }

        if ($exceptions['authentication'] ?? true) {
            $handler->renderable(function (AuthenticationException $e, $request) {
                if ($request->expectsJson()) {
                    return Handler::handleAuthentication($e);
                }
            });
        }

        if ($exceptions['authorization'] ?? true) {
            $handler->renderable(function (AuthorizationException $e, $request) {
                if ($request->expectsJson()) {
                    return Handler::handleAuthorization($e);
                }
            });
        }

        if ($exceptions['not_found'] ?? true) {
            $handler->renderable(function (ModelNotFoundException $e, $request) {
                if ($request->expectsJson()) {
                    return Handler::handleModelNotFound($e);
                }
            });

            $handler->renderable(function (NotFoundHttpException $e, $request) {
                if ($request->expectsJson()) {
                    return Handler::handleNotFound($e);
                }
            });
        }

        if ($exceptions['rate_limited'] ?? true) {
            $handler->renderable(function (TooManyRequestsHttpException $e, $request) {
                if ($request->expectsJson()) {
                    return Handler::handleRateLimited($e);
                }
            });
        }
    }
}
