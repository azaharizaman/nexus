<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Enable Audit Logging
    |--------------------------------------------------------------------------
    |
    | This option controls whether audit logging is enabled for your application.
    | When disabled, no audit logs will be created.
    |
    */
    'enabled' => env('AUDIT_LOGGING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the queue connection and queue name for asynchronous logging.
    | Async logging prevents performance impact on request processing.
    | Falls back to application's default queue connection if not specified.
    |
    */
    'queue_connection' => env('AUDIT_LOGGING_QUEUE_CONNECTION', config('queue.default')),
    'queue_name' => env('AUDIT_LOGGING_QUEUE_NAME', 'audit-logs'),

    /*
    |--------------------------------------------------------------------------
    | Storage Driver
    |--------------------------------------------------------------------------
    |
    | The storage driver for audit logs. Options: 'database', 'mongodb'
    | Database driver uses PostgreSQL with JSONB or MySQL with JSON columns.
    |
    */
    'storage_driver' => env('AUDIT_LOGGING_STORAGE_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Log Retention Period
    |--------------------------------------------------------------------------
    |
    | Number of days to retain audit logs before they can be archived/purged.
    | Default is 2555 days (7 years) for compliance with SOX, GAAP requirements.
    | Minimum recommended: 365 days (1 year)
    |
    */
    'retention_days' => env('AUDIT_LOGGING_RETENTION_DAYS', 2555),

    /*
    |--------------------------------------------------------------------------
    | Sensitive Fields Masking
    |--------------------------------------------------------------------------
    |
    | List of field names that should be masked in audit logs for security.
    | Values for these fields will be replaced with '[REDACTED]'
    |
    */
    'mask_sensitive_fields' => [
        'password',
        'password_confirmation',
        'token',
        'secret',
        'api_key',
        'api_secret',
        'access_token',
        'refresh_token',
        'credit_card',
        'card_number',
        'cvv',
        'ssn',
        'social_security_number',
    ],

    /*
    |--------------------------------------------------------------------------
    | Log System Events
    |--------------------------------------------------------------------------
    |
    | Whether to log system-generated events (cron jobs, queue workers, CLI).
    | System events will have causer_type='system' and null causer_id.
    |
    */
    'log_system_events' => env('AUDIT_LOGGING_SYSTEM_EVENTS', true),

    /*
    |--------------------------------------------------------------------------
    | Enable Before/After State Capture
    |--------------------------------------------------------------------------
    |
    | Whether to capture before/after states for model updates.
    | This provides detailed audit trail but increases storage requirements.
    | Can be overridden per-model using the Auditable trait.
    |
    */
    'enable_before_after' => env('AUDIT_LOGGING_BEFORE_AFTER', true),

    /*
    |--------------------------------------------------------------------------
    | High-Value Entity Notifications
    |--------------------------------------------------------------------------
    |
    | Enable notifications for high-value entity operations.
    | List entity types that should trigger admin notifications.
    |
    */
    'notify_high_value_events' => env('AUDIT_LOGGING_NOTIFY_HIGH_VALUE', false),
    'high_value_entities' => [
        'App\\Models\\Invoice',
        'App\\Models\\Payment',
        'App\\Models\\InventoryAdjustment',
        'App\\Models\\PurchaseOrder',
        'App\\Models\\SalesOrder',
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for audit log exports (CSV, JSON, PDF)
    |
    */
    'export' => [
        'max_records_csv' => 100000,
        'max_records_json' => 50000,
        'max_records_pdf' => 10000,
        'disk' => 'local', // Storage disk for export files
        'path' => 'exports/audit-logs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for audit log search and filtering
    |
    */
    'search' => [
        'max_per_page' => 1000,
        'default_per_page' => 50,
        'enable_full_text_search' => env('AUDIT_LOGGING_FULL_TEXT_SEARCH', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for performance optimization
    |
    */
    'performance' => [
        'batch_size' => 1000, // Batch size for bulk operations
        'chunk_size' => 500,  // Chunk size for large queries
    ],
];
