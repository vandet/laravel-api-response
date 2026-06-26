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
    | Control which exception types are intercepted. Set any to false to
    | let Laravel handle it with its default behaviour.
    */
    'exceptions' => [
        'validation'     => true,
        'authentication' => true,
        'authorization'  => true,
        'not_found'      => true,
        'rate_limited'   => true,
        'server_error'   => true,
    ],

];
