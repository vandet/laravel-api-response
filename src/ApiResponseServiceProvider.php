<?php

namespace Vandet\ApiResponse;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Vandet\ApiResponse\Exceptions\Handler;

class ApiResponseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/api-response.php', 'api-response');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'api-response');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/api-response.php' => config_path('api-response.php'),
            ], 'api-response-config');

            $this->publishes([
                __DIR__.'/../lang' => lang_path('vendor/api-response'),
            ], 'api-response-lang');

            $this->publishes([
                __DIR__.'/../stubs' => app_path('Exceptions/Handlers'),
            ], 'api-response-stubs');
        }

        if (! config('api-response.handle_exceptions', true)) {
            return;
        }

        $exceptions = config('api-response.exceptions', []);
        $handler    = $this->app->make(ExceptionHandler::class);

        // Registration order matters: renderable() is LIFO — last registered = first checked.
        // Broad handlers go first here so specific subclasses (registered after) take precedence.
        //
        // Note: ApiException is NOT registered here — it implements render() directly,
        // so Laravel calls it automatically before consulting these callbacks.
        //
        // Each config value supports three states:
        //   true       → use the package default handler
        //   false      → skip (do not register — Laravel handles it)
        //   'ClassName'→ resolve from container and call handle(\Throwable) — must implement ExceptionHandlerContract

        if (($cfg = $exceptions['server_error'] ?? true) !== false) {
            $handler->renderable(function (\Throwable $e, $request) use ($cfg) {
                if ($request->expectsJson()) {
                    return \is_string($cfg)
                        ? $this->app->make($cfg)->handle($e)
                        : Handler::handleServerError($e);
                }
            });
        }

        if (($cfg = $exceptions['http_error'] ?? true) !== false) {
            $handler->renderable(function (HttpException $e, $request) use ($cfg) {
                if ($request->expectsJson()) {
                    return \is_string($cfg)
                        ? $this->app->make($cfg)->handle($e)
                        : Handler::handleHttpError($e);
                }
            });
        }

        if (($cfg = $exceptions['validation'] ?? true) !== false) {
            $handler->renderable(function (ValidationException $e, $request) use ($cfg) {
                if ($request->expectsJson()) {
                    return \is_string($cfg)
                        ? $this->app->make($cfg)->handle($e)
                        : Handler::handleValidation($e);
                }
            });
        }

        if (($cfg = $exceptions['authentication'] ?? true) !== false) {
            $handler->renderable(function (AuthenticationException $e, $request) use ($cfg) {
                if ($request->expectsJson()) {
                    return \is_string($cfg)
                        ? $this->app->make($cfg)->handle($e)
                        : Handler::handleAuthentication($e);
                }
            });
        }

        if (($cfg = $exceptions['authorization'] ?? true) !== false) {
            $handler->renderable(function (AuthorizationException $e, $request) use ($cfg) {
                if ($request->expectsJson()) {
                    return \is_string($cfg)
                        ? $this->app->make($cfg)->handle($e)
                        : Handler::handleAuthorization($e);
                }
            });
        }

        if (($cfg = $exceptions['model_not_found'] ?? true) !== false) {
            $handler->renderable(function (ModelNotFoundException $e, $request) use ($cfg) {
                if ($request->expectsJson()) {
                    return \is_string($cfg)
                        ? $this->app->make($cfg)->handle($e)
                        : Handler::handleModelNotFound($e);
                }
            });
        }

        // NotFoundHttpException is an HttpException subclass — must be registered after
        // the generic HttpException handler so LIFO picks this one first.
        if (($cfg = $exceptions['route_not_found'] ?? true) !== false) {
            $handler->renderable(function (NotFoundHttpException $e, $request) use ($cfg) {
                if ($request->expectsJson()) {
                    return \is_string($cfg)
                        ? $this->app->make($cfg)->handle($e)
                        : Handler::handleNotFound($e);
                }
            });
        }

        // TooManyRequestsHttpException is an HttpException subclass — registered last
        // so LIFO picks it before the generic HttpException handler.
        if (($cfg = $exceptions['rate_limited'] ?? true) !== false) {
            $handler->renderable(function (TooManyRequestsHttpException $e, $request) use ($cfg) {
                if ($request->expectsJson()) {
                    return \is_string($cfg)
                        ? $this->app->make($cfg)->handle($e)
                        : Handler::handleRateLimited($e);
                }
            });
        }
    }
}
