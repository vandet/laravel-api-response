<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Exception Handler
    |--------------------------------------------------------------------------
    | Set to true to automatically convert Laravel exceptions into the
    | standard API response envelope. Set to false to handle manually.
    */
    'handle_exceptions' => true,

    /*
    |--------------------------------------------------------------------------
    | Exception Map
    |--------------------------------------------------------------------------
    | Each key controls how a specific exception type is handled:
    |
    |   true            → use the package's built-in handler (default)
    |   false           → skip — Laravel handles it with default behaviour
    |   'ClassName'     → use a custom class that implements ExceptionHandlerContract
    |
    | Example:
    |
    |   'validation'      => \App\Exceptions\Handlers\ValidationHandler::class,
    |   'server_error'    => false,
    |
    | Custom handler classes must implement:
    |   Vandet\ApiResponse\Contracts\ExceptionHandlerContract
    |
    | Publish stubs to get started:
    |   php artisan vendor:publish --tag=api-response-stubs
    */
    'exceptions' => [
        'validation'      => true,
        'authentication'  => true,
        'authorization'   => true,
        'model_not_found' => true,   // ModelNotFoundException  → "Resource not found."
        'route_not_found' => true,   // NotFoundHttpException   → "The requested resource was not found."
        'rate_limited'    => true,
        'http_error'      => true,
        'server_error'    => true,
    ],

];
