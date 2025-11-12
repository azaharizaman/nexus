<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Serial Numbering Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable the serial numbering system. When disabled, all
    | sequence generation operations will throw exceptions.
    |
    */

    'enabled' => env('SERIAL_NUMBERING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default Padding
    |--------------------------------------------------------------------------
    |
    | The default number of digits for zero-padding counters. For example,
    | padding of 5 will format counter 123 as "00123".
    | Valid range: 1-10
    |
    */

    'default_padding' => 5,

    /*
    |--------------------------------------------------------------------------
    | Default Reset Period
    |--------------------------------------------------------------------------
    |
    | The default reset period for new sequences. Valid values are:
    | - 'never': Never reset automatically
    | - 'daily': Reset at start of each day
    | - 'monthly': Reset at start of each month
    | - 'yearly': Reset at start of each year
    |
    */

    'default_reset_period' => 'yearly',

    /*
    |--------------------------------------------------------------------------
    | Enable Manual Override
    |--------------------------------------------------------------------------
    |
    | Allow administrators to manually override generated serial numbers.
    | This should be used sparingly and requires super-admin permissions.
    |
    */

    'enable_override' => env('SERIAL_NUMBERING_ENABLE_OVERRIDE', false),

    /*
    |--------------------------------------------------------------------------
    | Log Generations
    |--------------------------------------------------------------------------
    |
    | Enable logging of all serial number generations to the audit table.
    | This provides a complete audit trail of all generated numbers.
    |
    */

    'log_generations' => true,

    /*
    |--------------------------------------------------------------------------
    | Cache Sequences
    |--------------------------------------------------------------------------
    |
    | Cache sequence configurations in memory to reduce database queries.
    | Note: Counter values are NEVER cached to ensure atomicity.
    |
    */

    'cache_sequences' => env('SERIAL_NUMBERING_CACHE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL
    |--------------------------------------------------------------------------
    |
    | Time-to-live for cached sequence configurations in seconds.
    | Default: 3600 seconds (1 hour)
    |
    */

    'cache_ttl' => 3600,

    /*
    |--------------------------------------------------------------------------
    | Supported Variables
    |--------------------------------------------------------------------------
    |
    | List of supported pattern variables and their descriptions.
    | These variables can be used in sequence patterns.
    |
    */

    'variables' => [
        'YEAR' => '4-digit year (e.g., 2025)',
        'YEAR:2' => '2-digit year (e.g., 25)',
        'MONTH' => '2-digit month (e.g., 01, 12)',
        'DAY' => '2-digit day (e.g., 01, 31)',
        'COUNTER' => 'Auto-incrementing counter with default padding',
        'COUNTER:N' => 'Counter with N-digit padding (e.g., COUNTER:5 = 00001)',
        'PREFIX' => 'Custom prefix from sequence configuration',
        'TENANT' => 'Tenant code',
        'DEPARTMENT' => 'Department code',
    ],

];
