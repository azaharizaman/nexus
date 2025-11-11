<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Activity Logging Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default activity logging implementation that
    | will be used by the application. You may set this to any of the
    | implementations defined below.
    |
    | Supported: "spatie", "database", "null"
    |
    */

    'activity_logger' => env('ACTIVITY_LOGGER', 'spatie'),

    /*
    |--------------------------------------------------------------------------
    | Search Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default search implementation that will be
    | used by the application. You may set this to any of the implementations
    | defined below.
    |
    | Supported: "scout", "database", "meilisearch", "null"
    |
    */

    'search_driver' => env('SEARCH_DRIVER', 'scout'),

    /*
    |--------------------------------------------------------------------------
    | Token Service Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default API token implementation that will be
    | used by the application for API authentication.
    |
    | Supported: "sanctum", "jwt", "session"
    |
    */

    'token_service' => env('TOKEN_SERVICE', 'sanctum'),
];
