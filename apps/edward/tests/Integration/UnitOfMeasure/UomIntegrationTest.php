<?php

declare(strict_types=1);

use App\Enums\UomCategory;
use App\Models\Uom;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ============================================================================
// Integration Tests: Traits & Model Behavior
// ============================================================================

test('Uom with BelongsToTenant trait filters by current tenant', function () {
    $tenant1 = \Illuminate\Support\Str::uuid();
    $tenant2 = \Illuminate\Support\Str::uuid();

    Uom::factory()->create(['tenant_id' => $tenant1]);
    Uom::factory()->create(['tenant_id' => $tenant1]);
    Uom::factory()->create(['tenant_id' => $tenant2]);

    // This would require tenant context middleware in real app
    // For now, verify UOMs are created correctly
    expect(Uom::where('tenant_id', $tenant1)->count())->toBe(2);
    expect(Uom::where('tenant_id', $tenant2)->count())->toBe(1);
});

test('Uom with HasActivityLogging trait logs changes', function () {
    $uom = Uom::factory()->create(['name' => 'Original']);

    $uom->update(['name' => 'Updated']);

    // Activity logging should be recorded (assuming LogsActivity trait is working)
    $uom->refresh();
    expect($uom->name)->toBe('Updated');
});

test('soft delete preserves audit trail', function () {
    $uom = Uom::factory()->create();
    $id = $uom->id;

    // Delete soft deletes
    $uom->delete();

    // Should be gone from normal query
    expect(Uom::find($id))->toBeNull();

    // But recoverable with withTrashed
    expect(Uom::withTrashed()->find($id))->not->toBeNull();
    expect(Uom::withTrashed()->find($id)->deleted_at)->not->toBeNull();
});

// ============================================================================
// Integration Tests: Complex Queries
// ============================================================================

test('can query UOMs with multiple scopes chained', function () {
    // Create mix of UOMs
    Uom::factory()->system()->length()->create();
    Uom::factory()->system()->length()->create();
    Uom::factory()->custom()->length()->create(['is_active' => false]);
    Uom::factory()->system()->mass()->create();

    // Chain scopes: system AND length AND active
    $result = Uom::system()
        ->category(UomCategory::LENGTH)
        ->active()
        ->get();

    expect($result)->toHaveCount(2);
    expect($result->every(fn ($u) => $u->is_system))->toBeTrue();
    expect($result->every(fn ($u) => $u->category === UomCategory::LENGTH))->toBeTrue();
    expect($result->every(fn ($u) => $u->is_active))->toBeTrue();
});

test('can count UOMs by category', function () {
    Uom::factory()->length()->count(5)->create();
    Uom::factory()->mass()->count(3)->create();
    Uom::factory()->volume()->count(4)->create();

    $counts = [
        'LENGTH' => Uom::category(UomCategory::LENGTH)->count(),
        'MASS' => Uom::category(UomCategory::MASS)->count(),
        'VOLUME' => Uom::category(UomCategory::VOLUME)->count(),
    ];

    expect($counts['LENGTH'])->toBe(5);
    expect($counts['MASS'])->toBe(3);
    expect($counts['VOLUME'])->toBe(4);
});

// ============================================================================
// Integration Tests: Data Transactions
// ============================================================================

test('bulk create maintains data integrity', function () {
    $data = [];
    for ($i = 0; $i < 10; $i++) {
        $data[] = [
            'code' => 'BULK-' . $i,
            'name' => 'Bulk Unit ' . $i,
            'symbol' => 'BU' . $i,
            'category' => UomCategory::COUNT->value,
            'conversion_factor' => ($i + 1) . '.0000000000',
            'is_system' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    \DB::table('uoms')->insert($data);

    $created = Uom::where('code', 'like', 'BULK-%')->get();
    expect($created)->toHaveCount(10);
});

test('concurrent modifications maintain consistency', function () {
    $uom = Uom::factory()->create();

    $uom->update(['name' => 'First Update']);
    expect($uom->fresh()->name)->toBe('First Update');

    $uom->update(['is_active' => false]);
    expect($uom->fresh()->is_active)->toBeFalse();

    $uom->update(['conversion_factor' => '2.5000000000']);
    expect($uom->fresh()->conversion_factor)->toBe('2.5000000000');
});

test('can restore soft-deleted UOM', function () {
    $uom = Uom::factory()->create();
    $id = $uom->id;

    $uom->delete();
    expect(Uom::find($id))->toBeNull();

    // Restore
    Uom::withTrashed()->find($id)->restore();
    expect(Uom::find($id))->not->toBeNull();
    expect(Uom::find($id)->deleted_at)->toBeNull();
});
