<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Impersonation Timeout
    |--------------------------------------------------------------------------
    |
    | The maximum duration (in seconds) that an impersonation session can remain
    | active. After this time, the impersonation will automatically expire.
    | Default: 3600 seconds (1 hour)
    |
    */

    'impersonation_timeout' => env('ERP_IMPERSONATION_TIMEOUT', 3600),

    /*
    |--------------------------------------------------------------------------
    | Tenant Cache TTL
    |--------------------------------------------------------------------------
    |
    | The time-to-live (in seconds) for tenant data stored in cache.
    | Default: 3600 seconds (1 hour)
    |
    */

    'tenant_cache_ttl' => env('ERP_TENANT_CACHE_TTL', 3600),

];
