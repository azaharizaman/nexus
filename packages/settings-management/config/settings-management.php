<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Settings Table Name
    |--------------------------------------------------------------------------
    |
    | The database table used to store settings.
    |
    */
    'table_name' => env('SETTINGS_TABLE_NAME', 'settings'),

    /*
    |--------------------------------------------------------------------------
    | Settings History Table Name
    |--------------------------------------------------------------------------
    |
    | The database table used to store settings change history.
    |
    */
    'history_table_name' => env('SETTINGS_HISTORY_TABLE_NAME', 'settings_history'),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for settings.
    |
    */
    'cache' => [
        'enabled' => env('SETTINGS_CACHE_ENABLED', true),
        'ttl' => env('SETTINGS_CACHE_TTL', 3600), // 1 hour in seconds
        'prefix' => env('SETTINGS_CACHE_PREFIX', 'settings'),
        'driver' => env('SETTINGS_CACHE_DRIVER', null), // null uses default cache driver
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption Configuration
    |--------------------------------------------------------------------------
    |
    | Settings with type 'encrypted' will be encrypted using Laravel's
    | encryption. Ensure APP_KEY is set in production.
    |
    */
    'encryption' => [
        'enabled' => env('SETTINGS_ENCRYPTION_ENABLED', true),
        'cipher' => 'AES-256-CBC', // Laravel default
    ],

    /*
    |--------------------------------------------------------------------------
    | Scope Hierarchy
    |--------------------------------------------------------------------------
    |
    | Define the order of scope resolution. Settings are resolved from
    | left to right, with the first found value being returned.
    | Default: user -> module -> tenant -> system
    |
    */
    'scope_hierarchy' => ['user', 'module', 'tenant', 'system'],

    /*
    |--------------------------------------------------------------------------
    | Supported Types
    |--------------------------------------------------------------------------
    |
    | List of supported setting types for validation.
    |
    */
    'supported_types' => [
        'string',
        'integer',
        'boolean',
        'array',
        'json',
        'encrypted',
    ],

    /*
    |--------------------------------------------------------------------------
    | Key Validation Pattern
    |--------------------------------------------------------------------------
    |
    | Regular expression pattern for validating setting keys.
    | Default allows: alphanumeric, dots, underscores, and hyphens
    | Example: email.smtp.host, module_name.setting-key
    |
    */
    'key_pattern' => '/^[a-z0-9._-]+$/i',

    /*
    |--------------------------------------------------------------------------
    | Max Key Length
    |--------------------------------------------------------------------------
    |
    | Maximum length for setting keys.
    |
    */
    'max_key_length' => 255,

    /*
    |--------------------------------------------------------------------------
    | Scout Configuration
    |--------------------------------------------------------------------------
    |
    | Enable search functionality for settings using Laravel Scout.
    |
    */
    'scout' => [
        'enabled' => env('SETTINGS_SCOUT_ENABLED', true),
        'index_name' => env('SETTINGS_SCOUT_INDEX', 'settings'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    |
    | Define required permissions for different operations.
    |
    */
    'permissions' => [
        'view_any' => 'view-settings',
        'view' => 'view-setting',
        'create' => 'create-setting',
        'update' => 'update-setting',
        'delete' => 'delete-setting',
        'export' => 'export-settings',
        'import' => 'import-settings',
        'view_encrypted' => 'view-encrypted-settings',
        'manage_system' => 'manage-system-settings', // Required for system-level settings
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Enable audit logging for setting changes.
    |
    */
    'audit_logging' => [
        'enabled' => env('SETTINGS_AUDIT_LOGGING_ENABLED', true),
        'log_only' => ['key', 'value', 'type', 'scope', 'metadata'], // Fields to log
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | System-level default settings that are seeded on installation.
    |
    */
    'defaults' => [
        // Application Settings
        'app.name' => [
            'value' => 'Laravel ERP',
            'type' => 'string',
            'metadata' => [
                'label' => 'Application Name',
                'description' => 'The name of the ERP application',
                'category' => 'general',
            ],
        ],
        'app.timezone' => [
            'value' => 'UTC',
            'type' => 'string',
            'metadata' => [
                'label' => 'Default Timezone',
                'description' => 'Default timezone for the application',
                'category' => 'general',
            ],
        ],
        'app.locale' => [
            'value' => 'en',
            'type' => 'string',
            'metadata' => [
                'label' => 'Default Locale',
                'description' => 'Default language locale',
                'category' => 'general',
            ],
        ],

        // Email Settings
        'email.from.address' => [
            'value' => 'noreply@example.com',
            'type' => 'string',
            'metadata' => [
                'label' => 'From Email Address',
                'description' => 'Default email address for outgoing emails',
                'category' => 'email',
            ],
        ],
        'email.from.name' => [
            'value' => 'Laravel ERP',
            'type' => 'string',
            'metadata' => [
                'label' => 'From Email Name',
                'description' => 'Default name for outgoing emails',
                'category' => 'email',
            ],
        ],

        // Pagination
        'pagination.per_page' => [
            'value' => 15,
            'type' => 'integer',
            'metadata' => [
                'label' => 'Items Per Page',
                'description' => 'Default number of items per page in listings',
                'category' => 'ui',
            ],
        ],
    ],
];
