# laravel-api-response

[![Latest Version on Packagist](https://img.shields.io/packagist/v/vandet/laravel-api-response.svg)](https://packagist.org/packages/vandet/laravel-api-response)
[![CI](https://github.com/vandet/laravel-api-response/actions/workflows/ci.yml/badge.svg)](https://github.com/vandet/laravel-api-response/actions/workflows/ci.yml)
[![PHP Version](https://img.shields.io/packagist/php-v/vandet/laravel-api-response.svg)](https://packagist.org/packages/vandet/laravel-api-response)
[![License](https://img.shields.io/packagist/l/vandet/laravel-api-response.svg)](https://packagist.org/packages/vandet/laravel-api-response)

A Laravel package that enforces a consistent API response envelope — success, paginated, error, and bulk partial failure — so all services speak the same shape without hand-rolling `ResponseFactory` in each one.

---

## Requirements

- PHP 8.2+
- Laravel 10, 11, 12, or 13

---

## Installation

### Option 1 — Composer (recommended)

```bash
composer require vandet/laravel-api-response
```

Laravel auto-discovers the service provider — no manual registration needed.

### Option 2 — Clone the repository

Use this when you want to contribute, customise the source, or install without Packagist.

**1. Clone into your project**

```bash
git clone https://github.com/vandet/laravel-api-response.git packages/laravel-api-response
```

**2. Add the local path repository to your `composer.json`**

```json
"repositories": [
    {
        "type": "path",
        "url": "./packages/laravel-api-response"
    }
]
```

**3. Require the package**

```bash
composer require vandet/laravel-api-response
```

Composer symlinks the cloned folder into `vendor/` — any changes you make to the source are reflected immediately without re-running `composer update`.

### Publish the config (optional)

```bash
php artisan vendor:publish --tag=api-response-config
```

This creates `config/api-response.php` where you can toggle exception handling per type.

---

## Usage

### ResponseFactory

Import once at the top of your controller:

```php
use Vandet\ApiResponse\Http\ResponseFactory;
```

#### Success — single resource

```php
return ResponseFactory::success($user, 'User retrieved successfully.');
```

```json
{ "success": true, "message": "User retrieved successfully.", "data": { ... } }
```

#### Success — created (201)

```php
return ResponseFactory::created($user, 'User created successfully.');
```

#### Success — accepted async job (202)

```php
return ResponseFactory::accepted('Import queued successfully.');
```

#### Success — paginated collection

Pass a Laravel `LengthAwarePaginator` directly. Call `->withQueryString()` on the paginator to preserve filter/sort params in links.

```php
$users = User::paginate(20)->withQueryString();

return ResponseFactory::paginated($users, 'Users retrieved successfully.');
```

```json
{
    "success": true,
    "message": "Users retrieved successfully.",
    "data": [...],
    "pagination": { "current_page": 1, "last_page": 5, "per_page": 20, "total": 82, "from": 1, "to": 20 },
    "links": { "first": "...", "last": "...", "next": "...", "prev": null }
}
```

#### Success — with reference data

```php
return ResponseFactory::withIncluded($users, [
    'roles'    => Role::all()->toArray(),
    'statuses' => Status::all()->toArray(),
], 'Users retrieved successfully.');
```

#### Delete (204 — no body)

```php
return ResponseFactory::deleted();
```

#### Validation error (422)

**With FormRequest (recommended) — no manual call needed.**
The exception handler catches the `ValidationException` that `FormRequest` throws automatically
and converts it to the standard envelope for you.

```php
// FormRequest — just type-hint it, validation + response are automatic
public function store(StoreUserRequest $request): JsonResponse
{
    $dto = UserDTO::fromRequest($request);
    // ...
}
```

```json
{ "success": false, "message": "Validation failed.", "code": "VALIDATION_FAILED", "errors": { "email": ["Email is required."] } }
```

**With a manual validator** — call `ResponseFactory::validationError()` yourself:

```php
$validator = Validator::make($request->all(), [
    'email' => ['required', 'email'],
]);

if ($validator->fails()) {
    return ResponseFactory::validationError($validator->errors()->toArray());
}
```

#### Not found (404)

```php
use Vandet\ApiResponse\Constants\ErrorCodes;

return ResponseFactory::notFound(ErrorCodes::USER_NOT_FOUND, 'User not found.');
```

#### Unauthorized (401) / Forbidden (403) / Conflict (409)

```php
return ResponseFactory::unauthorized(ErrorCodes::AUTH_TOKEN_EXPIRED, 'Token has expired.');
return ResponseFactory::forbidden(ErrorCodes::AUTH_USER_FORBIDDEN, 'You do not have permission.');
return ResponseFactory::conflict(ErrorCodes::USER_EMAIL_DUPLICATE, 'Email already registered.');
```

#### Bulk partial failure (207)

```php
return ResponseFactory::bulkPartialFailure([
    'created' => 1,
    'failed'  => 1,
    'items'   => [
        ['index' => 0, 'success' => true,  'id' => '550e8400-...'],
        ['index' => 1, 'success' => false, 'code' => 'USER_EMAIL_DUPLICATE', 'message' => 'Email already registered.'],
    ],
]);
```

#### Rate limited (429) / Server error (500)

```php
return ResponseFactory::rateLimited();
return ResponseFactory::serverError('Something went wrong.');
```

---

## Error Codes

All standard error codes are available as constants:

```php
use Vandet\ApiResponse\Constants\ErrorCodes;

ErrorCodes::AUTH_TOKEN_EXPIRED
ErrorCodes::USER_NOT_FOUND
ErrorCodes::VALIDATION_FAILED
ErrorCodes::RESOURCE_NOT_FOUND
ErrorCodes::SERVER_UNEXPECTED_ERROR
// ... and 35 more
```

See [`src/Constants/ErrorCodes.php`](src/Constants/ErrorCodes.php) for the full list, or refer to [`04-error-code-standard.md`](../../document/docs/api-standards/04-error-code-standard.md).

---

## Exception Handler

The package automatically intercepts Laravel exceptions on JSON requests and converts them to the standard envelope.

| Exception | HTTP | Code |
|-----------|------|------|
| `ValidationException` | 422 | `VALIDATION_FAILED` |
| `AuthenticationException` | 401 | `AUTH_TOKEN_MISSING` |
| `AuthorizationException` | 403 | `AUTH_USER_FORBIDDEN` |
| `ModelNotFoundException` | 404 | `RESOURCE_NOT_FOUND` |
| `NotFoundHttpException` | 404 | `RESOURCE_NOT_FOUND` |
| `TooManyRequestsHttpException` | 429 | `SERVER_RATE_LIMITED` |
| `Throwable` (catch-all) | 500 | `SERVER_UNEXPECTED_ERROR` |

Only requests with `Accept: application/json` are intercepted — web/HTML routes are unaffected.

### Disabling the exception handler

To disable all automatic exception handling:

```php
// config/api-response.php
'handle_exceptions' => false,
```

To disable specific exception types:

```php
'exceptions' => [
    'validation'     => true,
    'authentication' => true,
    'authorization'  => false, // handle manually
    'not_found'      => true,
    'rate_limited'   => true,
    'server_error'   => true,
],
```

### Conflict with an existing exception handler

If your service already has custom exception handling, the package renderables take priority for matched types. To opt out of specific types (see above) and handle them yourself, use `$exceptions['type'] => false` in the config.

---

## Using ResponseFactory in a Custom Exception Handler

You can use `ResponseFactory` directly inside your own exception handler alongside or instead of the package's built-in renderables.

### Laravel 11 — `bootstrap/app.php`

```php
use Illuminate\Foundation\Configuration\Exceptions;
use Vandet\ApiResponse\Http\ResponseFactory;
use Vandet\ApiResponse\Constants\ErrorCodes;
use App\Exceptions\PaymentFailedException;
use App\Exceptions\TenantSuspendedException;

->withExceptions(function (Exceptions $exceptions) {

    // Custom domain exception
    $exceptions->renderable(function (PaymentFailedException $e, $request) {
        if ($request->expectsJson()) {
            return ResponseFactory::conflict(
                ErrorCodes::PAYMENT_FAILED,
                $e->getMessage()
            );
        }
    });

    // Another domain exception
    $exceptions->renderable(function (TenantSuspendedException $e, $request) {
        if ($request->expectsJson()) {
            return ResponseFactory::forbidden(
                ErrorCodes::TENANT_SUSPENDED,
                'This account has been suspended.'
            );
        }
    });

})
```

### Laravel 10 — `app/Exceptions/Handler.php`

```php
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Vandet\ApiResponse\Http\ResponseFactory;
use Vandet\ApiResponse\Constants\ErrorCodes;
use App\Exceptions\PaymentFailedException;
use App\Exceptions\TenantSuspendedException;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        $this->renderable(function (PaymentFailedException $e, Request $request) {
            if ($request->expectsJson()) {
                return ResponseFactory::conflict(
                    ErrorCodes::PAYMENT_FAILED,
                    $e->getMessage()
                );
            }
        });

        $this->renderable(function (TenantSuspendedException $e, Request $request) {
            if ($request->expectsJson()) {
                return ResponseFactory::forbidden(
                    ErrorCodes::TENANT_SUSPENDED,
                    'This account has been suspended.'
                );
            }
        });
    }
}
```

### Custom domain exception pattern

Define your exception with a built-in error code so the handler stays clean:

```php
class PaymentFailedException extends \RuntimeException
{
    public function __construct(string $message = 'Payment gateway rejected the transaction.')
    {
        parent::__construct($message);
    }
}
```

Then throw it anywhere in your application:

```php
throw new PaymentFailedException('Card declined.');
```

The handler catches it and returns:

```json
{ "success": false, "message": "Card declined.", "code": "PAYMENT_FAILED", "errors": {} }
```

---

### ApiException — built-in base class

The package ships with `ApiException`, a base class your domain exceptions can extend.
It stores the error code and HTTP status directly on the exception — no renderable registration needed.

```php
use Vandet\ApiResponse\Exceptions\ApiException;
use Vandet\ApiResponse\Constants\ErrorCodes;

class PaymentFailedException extends ApiException
{
    public function __construct(string $message = 'Payment gateway rejected the transaction.')
    {
        parent::__construct(ErrorCodes::PAYMENT_FAILED, $message, 422);
    }
}

class TenantSuspendedException extends ApiException
{
    public function __construct()
    {
        parent::__construct(ErrorCodes::TENANT_SUSPENDED, 'This account has been suspended.', 403);
    }
}
```

Throw from anywhere — controller, action, service — and the package handler responds automatically:

```php
// In an Action or Service
if ($tenant->isSuspended()) {
    throw new TenantSuspendedException();
}

// In a controller
throw new PaymentFailedException('Card declined.');
```

```json
{ "success": false, "message": "Card declined.", "code": "PAYMENT_FAILED", "errors": {} }
```

No need to register a `renderable()` for each exception type. All classes that extend `ApiException` are caught by the service provider automatically.

#### Generic one-off errors without a custom class

Use `ResponseFactory::error()` when you need a specific code and status without creating a dedicated exception class:

```php
use Vandet\ApiResponse\Http\ResponseFactory;
use Vandet\ApiResponse\Constants\ErrorCodes;

return ResponseFactory::error(ErrorCodes::ORDER_CANCELLED, 'Order has been cancelled.', 409);
```

### Tip — disable the built-in handler for types you own

If your service handles its own `ModelNotFoundException` with a domain-specific message,
disable the package's version in `config/api-response.php` to avoid conflicts:

```php
'exceptions' => [
    'not_found' => false, // I handle this myself
],
```

---

## Response Envelope Reference

```
// Success
{ "success": true,  "message": "...", "data": {} }
{ "success": true,  "message": "...", "data": [], "pagination": {}, "links": {} }
{ "success": true,  "message": "...", "data": [], "included": {} }

// Error
{ "success": false, "message": "...", "code": "DOMAIN_ENTITY_REASON", "errors": {} }

// Delete
HTTP 204 No Content

// Bulk partial (only error response that includes data)
{ "success": false, "message": "...", "code": "BULK_PARTIAL_FAILURE", "data": { "items": [] }, "errors": {} }
```

Optional fields (`pagination`, `links`, `included`, `meta`) are **omitted entirely** when absent — never `null`.

---

## Running Tests

```bash
composer install
./vendor/bin/phpunit
```

---

## Changelog

| Version | Date       | Change          |
|---------|------------|-----------------|
| 1.0.0   | 2026-06-26 | Initial release |
