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

        // Handlers are registered in broad-to-specific order.
        // server_error and http_error use explicit instanceof guards to defer to the more
        // specific handlers below, so correct behaviour does not depend on callback ordering.
        //
        // Note: ApiException is NOT registered here — it implements render() directly,
        // so Laravel calls it before consulting these callbacks.
        //
        // Each config value supports three states:
        //   true       → use the package default handler
        //   false      → skip (do not register — Laravel handles it)
        //   'ClassName'→ resolve from container and call handle(\Throwable) — must implement ExceptionHandlerContract

        if (($cfg = $exceptions['server_error'] ?? true) !== false) {
            $handler->renderable(function (\Throwable $e, $request) use ($cfg, $exceptions) {
                if (! $request->expectsJson()) {
                    return;
                }
                if (\is_string($cfg)) {
                    return $this->app->make($cfg)->handle($e);
                }
                // Defer to more specific handlers when they are enabled.
                // These guards ensure correct dispatch regardless of whether
                // renderable() callbacks are evaluated in FIFO or LIFO order.
                if ($e instanceof HttpException) return; // → http_error / route_not_found / rate_limited
                if (($exceptions['validation']      ?? true) !== false && $e instanceof ValidationException)     return;
                if (($exceptions['authentication']  ?? true) !== false && $e instanceof AuthenticationException) return;
                if (($exceptions['authorization']   ?? true) !== false && $e instanceof AuthorizationException)  return;
                if (($exceptions['model_not_found'] ?? true) !== false && $e instanceof ModelNotFoundException)  return;
                return Handler::handleServerError($e);
            });
        }

        if (($cfg = $exceptions['http_error'] ?? true) !== false) {
            $handler->renderable(function (HttpException $e, $request) use ($cfg, $exceptions) {
                if (! $request->expectsJson()) {
                    return;
                }
                if (\is_string($cfg)) {
                    return $this->app->make($cfg)->handle($e);
                }
                // NotFoundHttpException and TooManyRequestsHttpException have dedicated
                // handlers below — skip them here so the specific handler takes over.
                if ($e instanceof NotFoundHttpException || $e instanceof TooManyRequestsHttpException) {
                    return;
                }
                // AuthorizationException is converted to HttpException by prepareException()
                // before renderable callbacks run. Recover the original via getPrevious() so
                // the authorization handler fires with the correct code (AUTH_USER_FORBIDDEN).
                $authCfg = $exceptions['authorization'] ?? true;
                if ($authCfg !== false && $e->getPrevious() instanceof AuthorizationException) {
                    $original = $e->getPrevious();
                    return \is_string($authCfg)
                        ? $this->app->make($authCfg)->handle($original)
                        : Handler::handleAuthorization($original);
                }
                return Handler::handleHttpError($e);
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

        if (($cfg = $exceptions['route_not_found'] ?? true) !== false) {
            $handler->renderable(function (NotFoundHttpException $e, $request) use ($cfg, $exceptions) {
                if (! $request->expectsJson()) {
                    return;
                }
                // ModelNotFoundException is converted to NotFoundHttpException by prepareException().
                // Recover the original via getPrevious() so the model_not_found handler fires,
                // enabling independent config (custom class or disabled) per exception type.
                $modelCfg = $exceptions['model_not_found'] ?? true;
                if ($modelCfg !== false && $e->getPrevious() instanceof ModelNotFoundException) {
                    $original = $e->getPrevious();
                    return \is_string($modelCfg)
                        ? $this->app->make($modelCfg)->handle($original)
                        : Handler::handleModelNotFound($original);
                }
                return \is_string($cfg)
                    ? $this->app->make($cfg)->handle($e)
                    : Handler::handleNotFound($e);
            });
        }

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
