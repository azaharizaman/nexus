<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Production Settings
    |--------------------------------------------------------------------------
    */
    'production' => [
        'allow_overproduction' => env('MANUFACTURING_ALLOW_OVERPRODUCTION', false),
        'max_overproduction_pct' => env('MANUFACTURING_MAX_OVERPRODUCTION_PCT', 5.0),
        'scrap_default_pct' => env('MANUFACTURING_SCRAP_DEFAULT_PCT', 2.0),
        'backflush_material' => env('MANUFACTURING_BACKFLUSH_MATERIAL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Costing Settings
    |--------------------------------------------------------------------------
    */
    'costing' => [
        'method' => env('MANUFACTURING_COSTING_METHOD', 'standard'), // standard, actual, or average
        'overhead_allocation' => env('MANUFACTURING_OVERHEAD_ALLOCATION', 'labor_hours'), // labor_hours, machine_hours, activity_based
        'capture_labor_cost' => env('MANUFACTURING_CAPTURE_LABOR_COST', true),
        'capture_overhead_cost' => env('MANUFACTURING_CAPTURE_OVERHEAD_COST', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Quality Settings
    |--------------------------------------------------------------------------
    */
    'quality' => [
        'require_inspection' => env('MANUFACTURING_REQUIRE_INSPECTION', false),
        'auto_quarantine_on_fail' => env('MANUFACTURING_AUTO_QUARANTINE_ON_FAIL', true),
        'inspection_sampling_default' => env('MANUFACTURING_INSPECTION_SAMPLING_DEFAULT', 'AQL 2.5'),
    ],

    /*
    |--------------------------------------------------------------------------
    | MRP Settings
    |--------------------------------------------------------------------------
    */
    'mrp' => [
        'enabled' => env('MANUFACTURING_MRP_ENABLED', false),
        'planning_horizon_days' => env('MANUFACTURING_MRP_PLANNING_HORIZON_DAYS', 90),
        'safety_stock_days' => env('MANUFACTURING_MRP_SAFETY_STOCK_DAYS', 7),
        'reorder_point_method' => env('MANUFACTURING_MRP_REORDER_POINT_METHOD', 'fixed'), // fixed or calculated
    ],

    /*
    |--------------------------------------------------------------------------
    | Traceability Settings
    |--------------------------------------------------------------------------
    */
    'traceability' => [
        'require_lot_tracking' => env('MANUFACTURING_REQUIRE_LOT_TRACKING', false),
        'require_serial_tracking' => env('MANUFACTURING_REQUIRE_SERIAL_TRACKING', false),
        'lot_number_format' => env('MANUFACTURING_LOT_NUMBER_FORMAT', 'LOT-{YYYY}{MM}{DD}-{0000}'),
        'batch_genealogy_enabled' => env('MANUFACTURING_BATCH_GENEALOGY_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Capacity Planning
    |--------------------------------------------------------------------------
    */
    'capacity' => [
        'enabled' => env('MANUFACTURING_CAPACITY_PLANNING_ENABLED', false),
        'default_shifts_per_day' => env('MANUFACTURING_DEFAULT_SHIFTS_PER_DAY', 1),
        'default_hours_per_shift' => env('MANUFACTURING_DEFAULT_HOURS_PER_SHIFT', 8),
        'allow_overtime' => env('MANUFACTURING_ALLOW_OVERTIME', true),
        'alert_on_overload' => env('MANUFACTURING_ALERT_ON_OVERLOAD', true),
    ],
];
