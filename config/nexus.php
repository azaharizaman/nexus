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
        | 
        | NOTE: The following packages are listed as placeholders for future phases.
        | They are not yet implemented or refactored. Set to false by default.
        */
        'enabled' => [
            'backoffice' => true,
            'audit-log' => false,      // Not yet implemented
            'sequencing' => false,     // Not yet implemented
            'tenancy' => false,        // Not yet implemented
            'settings' => false,       // Not yet implemented
            'uom' => false,            // Not yet implemented
            'workflow' => false,       // Not yet implemented
            'accounting' => false,     // Not yet implemented
            'inventory' => false,      // Not yet implemented
        ],
    ],
];