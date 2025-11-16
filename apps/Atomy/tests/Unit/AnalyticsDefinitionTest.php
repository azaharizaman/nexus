<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\AnalyticsDefinition;

uses(RefreshDatabase::class);

it('can create and read analytics definitions', function () {
    $def = AnalyticsDefinition::create([
        'name' => 'sales-report',
        'label' => 'Monthly Sales',
        'schema' => ['date_range' => ['type'=>'date']],
        'metrics' => ['revenue' => ['source' => 'orders', 'field' => 'amount']],
    ]);

    expect(AnalyticsDefinition::where('name', 'sales-report')->exists())->toBeTrue();
});
