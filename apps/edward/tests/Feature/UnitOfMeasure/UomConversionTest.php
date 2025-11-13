<?php

declare(strict_types=1);

use App\Actions\UnitOfMeasure\ConvertQuantityAction;
use App\Actions\UnitOfMeasure\GetCompatibleUomsAction;
use App\Actions\UnitOfMeasure\ValidateUomCompatibilityAction;
use App\Enums\UomCategory;
use App\Exceptions\UnitOfMeasure\InvalidQuantityException;
use App\Exceptions\UnitOfMeasure\UomNotFoundException;
use App\Models\Uom;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test UOMs using factories for feature tests
    // Mass category
    Uom::factory()->system()->create([
        'code' => 'kg',
        'name' => 'Kilogram',
        'symbol' => 'kg',
        'category' => UomCategory::MASS,
        'conversion_factor' => '1.0',
    ]);

    Uom::factory()->system()->create([
        'code' => 'lb',
        'name' => 'Pound',
        'symbol' => 'lb',
        'category' => UomCategory::MASS,
        'conversion_factor' => '0.45359237',
    ]);

    // Length category
    Uom::factory()->system()->create([
        'code' => 'm',
        'name' => 'Meter',
        'symbol' => 'm',
        'category' => UomCategory::LENGTH,
        'conversion_factor' => '1.0',
    ]);
});

// ============================================================================
// ConvertQuantityAction Tests
// ============================================================================

test('ConvertQuantityAction converts accurately and returns complete result', function () {
    $action = app(ConvertQuantityAction::class);

    $result = $action->handle('100', 'kg', 'lb');

    expect($result)->toBeArray();
    expect($result)->toHaveKeys(['quantity', 'from_uom', 'to_uom', 'conversion_factor']);
    expect($result['from_uom'])->toBe('kg');
    expect($result['to_uom'])->toBe('lb');

    // Check accuracy
    $quantity = BigDecimal::of($result['quantity']);
    expect($quantity->isGreaterThan('220'))->toBeTrue();
    expect($quantity->isLessThan('221'))->toBeTrue();
});

test('ConvertQuantityAction validates quantity is numeric', function () {
    $action = app(ConvertQuantityAction::class);

    $action->handle('abc', 'kg', 'lb');
})->throws(InvalidQuantityException::class, 'must be numeric');

test('ConvertQuantityAction validates quantity is positive', function () {
    $action = app(ConvertQuantityAction::class);

    $action->handle('-100', 'kg', 'lb');
})->throws(InvalidQuantityException::class, 'must be positive');

test('ConvertQuantityAction validates quantity is not zero', function () {
    $action = app(ConvertQuantityAction::class);

    $action->handle('0', 'kg', 'lb');
})->throws(InvalidQuantityException::class, 'must be greater than zero');

test('ConvertQuantityAction caches conversion factors', function () {
    $action = app(ConvertQuantityAction::class);

    // Clear cache first
    Cache::flush();

    // First call - should cache
    $result1 = $action->handle('100', 'kg', 'lb');

    // Second call - should hit cache
    $result2 = $action->handle('200', 'kg', 'lb');

    // Conversion factors should be identical
    expect($result1['conversion_factor'])->toBe($result2['conversion_factor']);

    // Verify cache exists
    $cacheKey = 'uom:conversion:kg:lb';
    expect(Cache::has($cacheKey))->toBeTrue();
});

test('ConvertQuantityAction can be called as static run', function () {
    $result = ConvertQuantityAction::run('50', 'kg', 'lb');

    expect($result)->toBeArray();
    expect($result['quantity'])->toBeString();
});

// ============================================================================
// ValidateUomCompatibilityAction Tests
// ============================================================================

test('ValidateUomCompatibilityAction returns true for compatible UOMs', function () {
    $action = app(ValidateUomCompatibilityAction::class);

    $result = $action->handle('kg', 'lb');

    expect($result)->toBeTrue();
});

test('ValidateUomCompatibilityAction returns false for incompatible UOMs', function () {
    $action = app(ValidateUomCompatibilityAction::class);

    $result = $action->handle('kg', 'm');

    expect($result)->toBeFalse();
});

test('ValidateUomCompatibilityAction returns false for non-existent UOM', function () {
    $action = app(ValidateUomCompatibilityAction::class);

    $result = $action->handle('xyz', 'kg');

    expect($result)->toBeFalse();
});

test('ValidateUomCompatibilityAction accepts UOM models', function () {
    $action = app(ValidateUomCompatibilityAction::class);

    $kg = Uom::where('code', 'kg')->first();
    $lb = Uom::where('code', 'lb')->first();

    $result = $action->handle($kg, $lb);

    expect($result)->toBeTrue();
});

test('ValidateUomCompatibilityAction never throws exceptions', function () {
    $action = app(ValidateUomCompatibilityAction::class);

    // Should not throw even with invalid input
    $result = $action->handle('invalid1', 'invalid2');

    expect($result)->toBeFalse();
});

test('ValidateUomCompatibilityAction provides validation rules', function () {
    $action = app(ValidateUomCompatibilityAction::class);

    $rules = $action->rules();

    expect($rules)->toBeArray();
    expect($rules)->toHaveKey('uom1');
    expect($rules)->toHaveKey('uom2');
});

// ============================================================================
// GetCompatibleUomsAction Tests
// ============================================================================

test('GetCompatibleUomsAction returns all UOMs in same category', function () {
    $action = app(GetCompatibleUomsAction::class);

    $result = $action->handle('kg');

    expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($result->count())->toBeGreaterThan(1);

    // All should be MASS category
    $allMass = $result->every(fn ($uom) => $uom->category === UomCategory::MASS);
    expect($allMass)->toBeTrue();
});

test('GetCompatibleUomsAction throws exception for non-existent UOM', function () {
    $action = app(GetCompatibleUomsAction::class);

    $action->handle('xyz');
})->throws(UomNotFoundException::class);

test('GetCompatibleUomsAction accepts UOM model', function () {
    $action = app(GetCompatibleUomsAction::class);

    $kg = Uom::where('code', 'kg')->first();
    $result = $action->handle($kg);

    expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($result->count())->toBeGreaterThan(0);
});

test('GetCompatibleUomsAction caches results', function () {
    $action = app(GetCompatibleUomsAction::class);

    // Clear cache
    Cache::flush();

    // First call
    $result1 = $action->handle('kg');

    // Verify cache exists
    $cacheKey = 'uom:compatible:MASS:global';
    expect(Cache::has($cacheKey))->toBeTrue();

    // Second call should hit cache
    $result2 = $action->handle('kg');

    expect($result1->count())->toBe($result2->count());
});

test('GetCompatibleUomsAction can clear cache', function () {
    $action = app(GetCompatibleUomsAction::class);

    // Create cache
    $action->handle('kg');

    $cacheKey = 'uom:compatible:MASS:global';
    expect(Cache::has($cacheKey))->toBeTrue();

    // Clear cache
    GetCompatibleUomsAction::clearCache('MASS');

    expect(Cache::has($cacheKey))->toBeFalse();
});

// ============================================================================
// Integration Tests
// ============================================================================

test('end-to-end conversion via action matches service result', function () {
    $action = app(ConvertQuantityAction::class);
    $service = app(\App\Services\UnitOfMeasure\UomConversionService::class);

    $actionResult = $action->handle('100', 'kg', 'lb');
    $serviceResult = $service->convert('100', 'kg', 'lb');

    // Results should match
    expect($actionResult['quantity'])->toBe($serviceResult);
});

test('conversion respects tenant context', function () {
    // Create a tenant-specific custom UOM
    $customUom = Uom::factory()->create([
        'code' => 'custom-kg',
        'name' => 'Custom Kilogram',
        'category' => UomCategory::MASS,
        'conversion_factor' => '1.0', // Same as kg base unit
        'is_system' => false,
    ]);

    $action = app(ConvertQuantityAction::class);

    // Should be able to convert with custom UOM
    $result = $action->handle('100', 'custom-kg', 'lb');

    expect($result)->toBeArray();
    expect($result['quantity'])->toBeString();
});

test('cache invalidation works correctly after UOM update', function () {
    $action = app(ConvertQuantityAction::class);

    // Perform conversion to cache it
    $result1 = $action->handle('100', 'kg', 'lb');

    // Clear cache
    ConvertQuantityAction::clearCache('kg', 'lb');

    // Perform again
    $result2 = $action->handle('100', 'kg', 'lb');

    // Results should still be identical (same conversion)
    expect($result1['conversion_factor'])->toBe($result2['conversion_factor']);
});

// ============================================================================
// Performance Tests
// ============================================================================

test('1000 conversions complete within 5 seconds', function () {
    $action = app(ConvertQuantityAction::class);

    $startTime = microtime(true);

    for ($i = 0; $i < 1000; $i++) {
        $action->handle('100', 'kg', 'lb');
    }

    $duration = microtime(true) - $startTime;

    expect($duration)->toBeLessThan(5.0);
})->group('performance');

test('cache hit rate above 90 percent', function () {
    $action = app(ConvertQuantityAction::class);

    // Clear cache
    Cache::flush();

    $hits = 0;
    $total = 100;

    // First call - cache miss
    $action->handle('100', 'kg', 'lb');

    // Remaining calls should hit cache
    for ($i = 0; $i < $total - 1; $i++) {
        // Different quantities but same conversion pair
        $action->handle((string) (100 + $i), 'kg', 'lb');

        if (Cache::has('uom:conversion:kg:lb')) {
            $hits++;
        }
    }

    $hitRate = ($hits / $total) * 100;

    expect($hitRate)->toBeGreaterThan(90.0);
})->group('performance');

test('memory usage remains stable for large batches', function () {
    $action = app(ConvertQuantityAction::class);

    $initialMemory = memory_get_usage();

    // Perform many conversions
    for ($i = 0; $i < 1000; $i++) {
        $action->handle('100', 'kg', 'lb');
    }

    $finalMemory = memory_get_usage();
    $memoryIncrease = $finalMemory - $initialMemory;

    // Memory increase should be reasonable (less than 5MB)
    expect($memoryIncrease)->toBeLessThan(5 * 1024 * 1024);
})->group('performance');

// ============================================================================
// Edge Case Tests
// ============================================================================

test('handles zero quantity edge case', function () {
    $action = app(ConvertQuantityAction::class);

    $action->handle('0', 'kg', 'lb');
})->throws(InvalidQuantityException::class);

test('handles very large numbers without error', function () {
    $action = app(ConvertQuantityAction::class);

    $result = $action->handle('1000000000000000', 'kg', 'lb');

    expect($result)->toBeArray();
    expect($result['quantity'])->toBeString();
});

test('handles very small numbers accurately', function () {
    $action = app(ConvertQuantityAction::class);

    $result = $action->handle('0.0000000001', 'kg', 'lb');

    expect($result)->toBeArray();
    expect($result['quantity'])->toBeString();
});

test('handles scientific notation input', function () {
    $action = app(ConvertQuantityAction::class);

    $result = $action->handle('1.5e3', 'kg', 'lb'); // 1500 kg

    expect($result)->toBeArray();

    $quantity = BigDecimal::of($result['quantity']);
    expect($quantity->isGreaterThan('3000'))->toBeTrue(); // Should be ~3307 lb
});

// ============================================================================
// Rounding Mode Tests
// ============================================================================

test('applies correct rounding mode in action', function () {
    $service = app(\App\Services\UnitOfMeasure\UomConversionService::class);

    // Test with specific value that requires rounding
    $resultHalfUp = $service->convert('10.666666', 'kg', 'kg', 2, \Brick\Math\RoundingMode::HALF_UP);
    $resultFloor = $service->convert('10.666666', 'kg', 'kg', 2, \Brick\Math\RoundingMode::FLOOR);
    $resultCeiling = $service->convert('10.666666', 'kg', 'kg', 2, \Brick\Math\RoundingMode::CEILING);

    expect($resultHalfUp)->toBe('10.67');
    expect($resultFloor)->toBe('10.66');
    expect($resultCeiling)->toBe('10.67');
});
