<?php
return [
    // Map framework contracts to the package defaults. Consumers may override these.
    'models' => [
        'item' => \Nexus\Inventory\Models\Item::class,
        'location' => \Nexus\Inventory\Models\Location::class,
        'stock' => \Nexus\Inventory\Models\Stock::class,
        'stock_movement' => \Nexus\Inventory\Models\StockMovement::class,
        'unit' => \Nexus\Uom\Models\UomUnit::class,

        // Transaction models
        'transactions' => [
            'opening_balance' => \Nexus\Inventory\Models\Transactions\OpeningBalance::class,
            'stock_in' => \Nexus\Inventory\Models\Transactions\StockIn::class,
            'stock_out' => \Nexus\Inventory\Models\Transactions\StockOut::class,
            'stock_transfer' => \Nexus\Inventory\Models\Transactions\StockTransfer::class,
            'stock_adjustment' => \Nexus\Inventory\Models\Transactions\StockAdjustment::class,
        ],
    ],

    // Define custom table names for publishable migrations.
    'table_names' => [
        'items' => 'items',
        'locations' => 'locations',
        'stocks' => 'stocks',
        'stock_movements' => 'stock_movements',
        'opening_balances' => 'transaction_opening_balances',
        'stock_ins' => 'transaction_stock_ins',
        'stock_outs' => 'transaction_stock_outs',
        'stock_transfers' => 'transaction_stock_transfers',
        'stock_adjustments' => 'transaction_stock_adjustments',
    ],

    // Default precision for decimal quantities across the package.
    'quantity_precision' => 4,

    // Key used to generate serial numbers for stock movements.
    'serial_numbering_key' => 'inventory-movement',

    // Optional container binding that resolves to a serial number generator implementing a generate($key) method.
    'serial_number_generator_binding' => null,
];
