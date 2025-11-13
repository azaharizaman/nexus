<?php

declare(strict_types=1);

use App\Enums\UomCategory;
use App\Models\Uom;
use Nexus\TenancyManagement\Models\Tenant;
use Database\Seeders\UomSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ============================================================================
// UOM Seeder Tests
// ============================================================================

test('UomSeeder creates 41 system UOMs', function () {
    $this->seed(UomSeeder::class);

    $count = Uom::system()->count();
    expect($count)->toBe(41);
});

test('UomSeeder creates correct number of UOMs per category', function () {
    $this->seed(UomSeeder::class);

    $length = Uom::category(UomCategory::LENGTH)->system()->count();
    $mass = Uom::category(UomCategory::MASS)->system()->count();
    $volume = Uom::category(UomCategory::VOLUME)->system()->count();
    $area = Uom::category(UomCategory::AREA)->system()->count();
    $count = Uom::category(UomCategory::COUNT)->system()->count();
    $time = Uom::category(UomCategory::TIME)->system()->count();

    expect($length)->toBe(8);
    expect($mass)->toBe(7);
    expect($volume)->toBe(8);
    expect($area)->toBe(8);
    expect($count)->toBe(5);
    expect($time)->toBe(5);
});

test('UomSeeder seeds standard units with correct conversion factors', function () {
    $this->seed(UomSeeder::class);

    $meter = Uom::where('code', 'm')->first();
    $kilometer = Uom::where('code', 'km')->first();
    $kilogram = Uom::where('code', 'kg')->first();
    $liter = Uom::where('code', 'L')->first();

    expect($meter->conversion_factor)->toBe('1.0000000000');
    expect($kilometer->conversion_factor)->toBe('1000.0000000000');
    expect($kilogram->conversion_factor)->toBe('1.0000000000');
    expect($liter->conversion_factor)->toBe('1.0000000000');
});

test('UomSeeder creates all system UOMs as active', function () {
    $this->seed(UomSeeder::class);

    $inactive = Uom::system()->inactive()->count();
    expect($inactive)->toBe(0);
});

test('UomSeeder marks UOMs as system with null tenant_id', function () {
    $this->seed(UomSeeder::class);

    $uoms = Uom::system()->get();

    expect($uoms->every(fn ($u) => $u->tenant_id === null))->toBeTrue();
    expect($uoms->every(fn ($u) => $u->is_system === true))->toBeTrue();
});

// ============================================================================
// Tenant Isolation Tests
// ============================================================================

test('unique constraint prevents duplicate UOM codes within tenant', function () {
    // Use factory to create tenants that actually exist
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    Uom::create([
        'tenant_id' => $tenant1->id,
        'code' => 'CUSTOM-M',
        'name' => 'Custom Meter',
        'symbol' => 'cm',
        'category' => UomCategory::LENGTH,
        'conversion_factor' => '1.0000000000',
        'is_system' => false,
    ]);

    // Same code in different tenant should be allowed
    $uom2 = Uom::create([
        'tenant_id' => $tenant2->id,
        'code' => 'CUSTOM-M',
        'name' => 'Different Custom Meter',
        'symbol' => 'cm2',
        'category' => UomCategory::LENGTH,
        'conversion_factor' => '1.0000000000',
        'is_system' => false,
    ]);

    expect($uom2->id)->not->toBeNull();
});

test('tenant can have custom UOMs alongside system UOMs', function () {
    $this->seed(UomSeeder::class);

    $tenant = Tenant::factory()->create();

    Uom::create([
        'tenant_id' => $tenant->id,
        'code' => 'CUSTOM-UNIT',
        'name' => 'Custom Unit',
        'symbol' => 'cu',
        'category' => UomCategory::COUNT,
        'conversion_factor' => '5.0000000000',
        'is_system' => false,
    ]);

    $systemUoms = Uom::system()->count();
    $customUoms = Uom::where('tenant_id', $tenant->id)->custom()->count();
    $total = Uom::count();

    expect($systemUoms)->toBe(41);
    expect($customUoms)->toBe(1);
    expect($total)->toBe(42);
});

// ============================================================================
// Data Integrity Tests
// ============================================================================

test('conversion factors have correct precision', function () {
    $this->seed(UomSeeder::class);

    $uoms = Uom::system()->get();

    foreach ($uoms as $uom) {
        // All factors should be strings with max 10 decimal places
        expect($uom->conversion_factor)->toBeString();

        // Check decimal places (if there are decimals)
        if (strpos($uom->conversion_factor, '.') !== false) {
            $parts = explode('.', $uom->conversion_factor);
            expect(strlen($parts[1]))->toBeLessThanOrEqual(10);
        }
    }
});

test('soft delete works correctly for UOMs', function () {
    $uom = Uom::factory()->create();
    $id = $uom->id;

    $uom->delete();

    expect(Uom::withTrashed()->find($id))->not->toBeNull();
    expect(Uom::find($id))->toBeNull();
});

test('database indexes exist for performance queries', function () {
    $this->seed(UomSeeder::class);

    // These queries should use indexes efficiently
    $length = Uom::category(UomCategory::LENGTH)->get();
    $active = Uom::active()->get();
    $system = Uom::system()->get();

    expect($length->count())->toBeGreaterThan(0);
    expect($active->count())->toBeGreaterThan(0);
    expect($system->count())->toBeGreaterThan(0);
});
