<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Nexus ERP Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains settings for the Nexus ERP orchestration
    | layer and atomic package integration.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Backoffice Package Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Nexus Backoffice organizational management package.
    |
    */
    'backoffice' => [
        /*
        | Enable automatic observer registration for backoffice models.
        | When disabled, model events will not trigger observers.
        */
        'enable_observers' => env('NEXUS_BACKOFFICE_ENABLE_OBSERVERS', true),

        /*
        | Enable automatic policy registration for backoffice authorization.
        | When disabled, authorization policies will not be registered.
        */
        'enable_policies' => env('NEXUS_BACKOFFICE_ENABLE_POLICIES', true),

        /*
        | Enable console commands for backoffice operations.
        | When disabled, backoffice commands will not be available.
        */
        'enable_commands' => env('NEXUS_BACKOFFICE_ENABLE_COMMANDS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Nexus Audit Log package integration.
    |
    */
    'audit_log' => [
        /*
        | Enable automatic activity logging across the ERP system.
        */
        'enabled' => env('NEXUS_AUDIT_LOG_ENABLED', true),

        /*
        | Default logger implementation to use.
        */
        'default_logger' => 'database',
    ],

    /*
    |--------------------------------------------------------------------------
    | Package Orchestration
    |--------------------------------------------------------------------------
    |
    | Configuration for atomic package orchestration and integration.
    |
    */
    'packages' => [
        /*
        | Automatically register atomic package orchestration providers.
        | Set to false to manually control package integration.
        */
        'auto_register' => env('NEXUS_PACKAGES_AUTO_REGISTER', true),

        /*
        | List of atomic packages to integrate when auto_register is enabled.
        */
        'enabled' => [
            'backoffice' => true,
            'audit-log' => true,
            'sequencing' => true,
            'tenancy' => true,
            'settings' => true,
            'uom' => true,
            'workflow' => true,
            'accounting' => true,
            'inventory' => true,
        ],
    ],
];