<?php

return [
    'conversion' => [
        'default_precision' => 4,
        'math_scale' => 12,
        'allow_custom_formulas' => false,
    ],

    'aliases' => [
        'preferred_first' => true,
    ],

    'packaging' => [
        'max_depth' => 3,
        'enforce_unique_paths' => true,
    ],

    'logging' => [
        'enabled' => true,
        'immutable' => true,
    ],

    'seeders' => [
        'class' => \Nexus\UomManagement\Database\Seeders\UomDatabaseSeeder::class,
        'publish_tag' => 'laravel-uom-management-seeders',
    ],
];
