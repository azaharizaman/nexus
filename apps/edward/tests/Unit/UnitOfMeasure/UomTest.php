<?php

declare(strict_types=1);

use App\Enums\UomCategory;
use App\Models\Uom;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ============================================================================
// UomCategory Enum Tests
// ============================================================================

test('UomCategory enum has all 6 categories', function () {
    $categories = UomCategory::cases();

    expect($categories)->toHaveCount(6);
    expect(UomCategory::LENGTH)->toBeInstanceOf(UomCategory::class);
    expect(UomCategory::MASS)->toBeInstanceOf(UomCategory::class);
    expect(UomCategory::VOLUME)->toBeInstanceOf(UomCategory::class);
    expect(UomCategory::AREA)->toBeInstanceOf(UomCategory::class);
    expect(UomCategory::COUNT)->toBeInstanceOf(UomCategory::class);
    expect(UomCategory::TIME)->toBeInstanceOf(UomCategory::class);
});

test('UomCategory label method returns human-readable labels', function () {
    expect(UomCategory::LENGTH->label())->toBe('Length');
    expect(UomCategory::MASS->label())->toBe('Mass');
    expect(UomCategory::VOLUME->label())->toBe('Volume');
    expect(UomCategory::AREA->label())->toBe('Area');
    expect(UomCategory::COUNT->label())->toBe('Count/Quantity');
    expect(UomCategory::TIME->label())->toBe('Time');
});

test('UomCategory baseUnit method returns correct base units', function () {
    expect(UomCategory::LENGTH->baseUnit())->toBe('m');
    expect(UomCategory::MASS->baseUnit())->toBe('kg');
    expect(UomCategory::VOLUME->baseUnit())->toBe('L');
    expect(UomCategory::AREA->baseUnit())->toBe('m²');
    expect(UomCategory::COUNT->baseUnit())->toBe('pc');
    expect(UomCategory::TIME->baseUnit())->toBe('s');
});

test('UomCategory values method returns array of values', function () {
    $values = UomCategory::values();

    expect($values)->toBeArray();
    expect($values)->toContain('LENGTH');
    expect($values)->toContain('MASS');
    expect($values)->toContain('VOLUME');
    expect($values)->toContain('AREA');
    expect($values)->toContain('COUNT');
    expect($values)->toContain('TIME');
});

test('UomCategory options method returns associative array', function () {
    $options = UomCategory::options();

    expect($options)->toBeArray();
    expect($options['LENGTH'])->toBe('Length');
    expect($options['MASS'])->toBe('Mass');
    expect($options['VOLUME'])->toBe('Volume');
});

// ============================================================================
// Uom Model Tests
// ============================================================================

test('can create a Uom instance', function () {
    $uom = Uom::factory()->create([
        'code' => 'm',
        'name' => 'Meter',
        'symbol' => 'm',
        'category' => UomCategory::LENGTH,
    ]);

    expect($uom)->toBeInstanceOf(Uom::class);
    expect($uom->code)->toBe('m');
    expect($uom->name)->toBe('Meter');
    expect($uom->category)->toBe(UomCategory::LENGTH);
});

test('Uom model casts category to enum', function () {
    $uom = Uom::factory()->create(['category' => 'MASS']);

    expect($uom->category)->toBe(UomCategory::MASS);
    expect($uom->category)->toBeInstanceOf(UomCategory::class);
});

test('Uom has BelongsToTenant trait', function () {
    $uom = Uom::factory()->create();

    expect(method_exists($uom, 'tenant'))->toBeTrue();
});

test('isBaseUnit returns true for conversion factor of 1', function () {
    $baseUnit = Uom::factory()->create(['conversion_factor' => '1.0000000000']);
    $derivedUnit = Uom::factory()->create(['conversion_factor' => '2.5000000000']);

    expect($baseUnit->isBaseUnit())->toBeTrue();
    expect($derivedUnit->isBaseUnit())->toBeFalse();
});

// ============================================================================
// Uom Query Scopes Tests
// ============================================================================

test('scopeActive returns only active UOMs', function () {
    Uom::factory()->count(3)->create(['is_active' => true]);
    Uom::factory()->count(2)->create(['is_active' => false]);

    $active = Uom::active()->get();

    expect($active)->toHaveCount(3);
    expect($active->every(fn ($u) => $u->is_active))->toBeTrue();
});

test('scopeInactive returns only inactive UOMs', function () {
    Uom::factory()->count(2)->create(['is_active' => true]);
    Uom::factory()->count(3)->create(['is_active' => false]);

    $inactive = Uom::inactive()->get();

    expect($inactive)->toHaveCount(3);
    expect($inactive->every(fn ($u) => ! $u->is_active))->toBeTrue();
});

test('scopeSystem returns only system UOMs', function () {
    Uom::factory()->count(3)->create(['is_system' => true]);
    Uom::factory()->count(2)->create(['is_system' => false]);

    $system = Uom::system()->get();

    expect($system)->toHaveCount(3);
    expect($system->every(fn ($u) => $u->is_system))->toBeTrue();
});

test('scopeCustom returns only custom UOMs', function () {
    Uom::factory()->count(2)->create(['is_system' => true]);
    Uom::factory()->count(3)->create(['is_system' => false]);

    $custom = Uom::custom()->get();

    expect($custom)->toHaveCount(3);
    expect($custom->every(fn ($u) => ! $u->is_system))->toBeTrue();
});

test('scopeCategory filters by category', function () {
    Uom::factory()->length()->count(2)->create();
    Uom::factory()->mass()->count(3)->create();
    Uom::factory()->volume()->count(2)->create();

    $length = Uom::category(UomCategory::LENGTH)->get();
    $mass = Uom::category(UomCategory::MASS)->get();

    expect($length)->toHaveCount(2);
    expect($mass)->toHaveCount(3);
    expect($length->every(fn ($u) => $u->category === UomCategory::LENGTH))->toBeTrue();
});

// ============================================================================
// Uom Factory Tests
// ============================================================================

test('UomFactory creates valid UOM', function () {
    $uom = Uom::factory()->create();

    expect($uom->id)->not->toBeNull();
    expect($uom->code)->not->toBeNull();
    expect($uom->name)->not->toBeNull();
    expect($uom->category)->toBeInstanceOf(UomCategory::class);
});

test('UomFactory system state creates system UOM', function () {
    $uom = Uom::factory()->system()->create();

    expect($uom->is_system)->toBeTrue();
    expect($uom->tenant_id)->toBeNull();
});

test('UomFactory custom state creates custom UOM', function () {
    $uom = Uom::factory()->custom()->create();

    expect($uom->is_system)->toBeFalse();
    expect($uom->tenant_id)->not->toBeNull();
});

test('UomFactory category states create correct category', function () {
    $length = Uom::factory()->length()->create();
    $mass = Uom::factory()->mass()->create();
    $volume = Uom::factory()->volume()->create();

    expect($length->category)->toBe(UomCategory::LENGTH);
    expect($mass->category)->toBe(UomCategory::MASS);
    expect($volume->category)->toBe(UomCategory::VOLUME);
});
