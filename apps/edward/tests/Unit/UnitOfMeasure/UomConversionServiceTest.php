<?php

declare(strict_types=1);

use App\Contracts\UomRepositoryContract;
use App\Enums\UomCategory;
use App\Exceptions\UnitOfMeasure\IncompatibleUomException;
use App\Exceptions\UnitOfMeasure\UomNotFoundException;
use App\Models\Uom;
use App\Services\UnitOfMeasure\UomConversionService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test UOMs using factories for unit tests
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

    Uom::factory()->system()->create([
        'code' => 'km',
        'name' => 'Kilometer',
        'symbol' => 'km',
        'category' => UomCategory::LENGTH,
        'conversion_factor' => '1000.0',
    ]);

    // Volume category
    Uom::factory()->system()->create([
        'code' => 'L',
        'name' => 'Liter',
        'symbol' => 'L',
        'category' => UomCategory::VOLUME,
        'conversion_factor' => '1.0',
    ]);

    Uom::factory()->system()->create([
        'code' => 'mL',
        'name' => 'Milliliter',
        'symbol' => 'mL',
        'category' => UomCategory::VOLUME,
        'conversion_factor' => '0.001',
    ]);

    // Helper function to create service
    $this->createService = function () {
        $repository = app(UomRepositoryContract::class);

        return new UomConversionService($repository);
    };
});

// ============================================================================
// Core Conversion Tests
// ============================================================================

test('converts mass units accurately using BigDecimal', function () {
    $service = ($this->createService)();

    // 100 kg to lb (should be approximately 220.462 lb)
    $result = $service->convert('100', 'kg', 'lb', 3);

    expect($result)->toBeString();

    $resultBd = BigDecimal::of($result);
    $expectedBd = BigDecimal::of('220.462');

    // Check within tolerance (0.0001%)
    $diff = $resultBd->minus($expectedBd)->abs();
    expect($diff->isLessThan('0.001'))->toBeTrue();
});

test('converts length units accurately', function () {
    $service = ($this->createService)();

    // 1000 m to km (should be 1 km)
    $result = $service->convert('1000', 'm', 'km', 3);

    expect($result)->toBe('1.000');
});

test('converts volume units accurately', function () {
    $service = ($this->createService)();

    // 1 L to mL (should be 1000 mL)
    $result = $service->convert('1', 'L', 'mL', 0);

    expect($result)->toBe('1000');
});

test('direct conversion returns input unchanged', function () {
    $service = ($this->createService)();

    $result = $service->convert('100.5', 'kg', 'kg', 2);

    expect($result)->toBe('100.50');
});

test('bidirectional conversion maintains precision', function () {
    $service = ($this->createService)();

    // Convert kg -> lb -> kg
    $original = '100';
    $toLb = $service->convert($original, 'kg', 'lb', 10);
    $backToKg = $service->convert($toLb, 'lb', 'kg', 10);

    $originalBd = BigDecimal::of($original);
    $resultBd = BigDecimal::of($backToKg);

    // Check within very tight tolerance
    $diff = $resultBd->minus($originalBd)->abs();
    expect($diff->isLessThan('0.0001'))->toBeTrue();
});

// ============================================================================
// Conversion to/from Base Unit Tests
// ============================================================================

test('converts to base unit correctly', function () {
    $service = ($this->createService)();

    // 100 lb to kg (base unit for mass)
    // 1 lb = 0.45359237 kg
    $result = $service->convertToBaseUnit('100', 'lb');

    $resultBd = BigDecimal::of($result);
    $expectedBd = BigDecimal::of('45.359237');

    $diff = $resultBd->minus($expectedBd)->abs();
    expect($diff->isLessThan('0.001'))->toBeTrue();
});

test('converts from base unit correctly', function () {
    $service = ($this->createService)();

    // 45.359237 kg to lb
    $result = $service->convertFromBaseUnit('45.359237', 'lb', 3);

    $resultBd = BigDecimal::of($result);
    $expectedBd = BigDecimal::of('100.000');

    $diff = $resultBd->minus($expectedBd)->abs();
    expect($diff->isLessThan('0.001'))->toBeTrue();
});

// ============================================================================
// Exception Handling Tests
// ============================================================================

test('throws exception for incompatible categories', function () {
    $service = ($this->createService)();

    $service->convert('100', 'kg', 'm');
})->throws(IncompatibleUomException::class, 'Cannot convert between incompatible categories: MASS and LENGTH');

test('throws exception for non-existent UOM code', function () {
    $service = ($this->createService)();

    $service->convert('100', 'xyz', 'kg');
})->throws(UomNotFoundException::class, 'Unit of measure not found: xyz');

test('IncompatibleUomException has correct HTTP code', function () {
    $exception = new IncompatibleUomException('MASS', 'LENGTH');

    expect($exception->getHttpStatusCode())->toBe(422);
    expect($exception->getFromCategory())->toBe('MASS');
    expect($exception->getToCategory())->toBe('LENGTH');
});

test('UomNotFoundException has correct HTTP code', function () {
    $exception = new UomNotFoundException('xyz');

    expect($exception->getHttpStatusCode())->toBe(404);
    expect($exception->getUomCode())->toBe('xyz');
});

// ============================================================================
// Rounding Mode Tests
// ============================================================================

test('applies HALF_UP rounding correctly', function () {
    $service = ($this->createService)();

    // Create a test case that requires rounding
    $result = $service->convert('10.666666', 'kg', 'kg', 2, RoundingMode::HALF_UP);

    expect($result)->toBe('10.67');
});

test('applies FLOOR rounding correctly', function () {
    $service = ($this->createService)();

    $result = $service->convert('10.666666', 'kg', 'kg', 2, RoundingMode::FLOOR);

    expect($result)->toBe('10.66');
});

test('applies CEILING rounding correctly', function () {
    $service = ($this->createService)();

    $result = $service->convert('10.661111', 'kg', 'kg', 2, RoundingMode::CEILING);

    expect($result)->toBe('10.67');
});

// ============================================================================
// Edge Case Tests
// ============================================================================

test('handles very large numbers without precision loss', function () {
    $service = ($this->createService)();

    $largeNumber = '1000000000000000'; // 1e15
    $result = $service->convert($largeNumber, 'kg', 'kg', 0);

    expect($result)->toBe($largeNumber);
});

test('handles very small numbers accurately', function () {
    $service = ($this->createService)();

    $smallNumber = '0.0000000001'; // 1e-10
    $result = $service->convert($smallNumber, 'kg', 'kg', 10);

    expect($result)->toBe($smallNumber);
});

test('accepts BigDecimal as input', function () {
    $service = ($this->createService)();

    $quantity = BigDecimal::of('100.5');
    $result = $service->convert($quantity, 'kg', 'lb', 3);

    expect($result)->toBeString();
    // Result should be approximately 221.563 lb
    $resultBd = BigDecimal::of($result);
    expect($resultBd->isGreaterThan('221'))->toBeTrue();
    expect($resultBd->isLessThan('222'))->toBeTrue();
});

test('uses target UOM precision when not specified', function () {
    $service = ($this->createService)();

    // Convert and check that result uses default precision (10 decimals max)
    $result = $service->convert('100', 'kg', 'lb');

    // Result should have limited decimals
    expect(strlen($result))->toBeLessThan(20);
});

// ============================================================================
// Performance Tests
// ============================================================================

test('conversion completes within performance threshold', function () {
    $service = ($this->createService)();

    $startTime = microtime(true);

    for ($i = 0; $i < 100; $i++) {
        $service->convert('100', 'kg', 'lb', 3);
    }

    $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
    $averageTime = $duration / 100;

    expect($averageTime)->toBeLessThan(5.0);
})->group('performance');

// ============================================================================
// Precision Validation Tests
// ============================================================================

test('result preserves precision as string', function () {
    $service = ($this->createService)();

    $result = $service->convert('100', 'kg', 'lb', 10);

    // Should return string with 10 decimal places
    expect($result)->toBeString();
    expect($result)->toMatch('/^\d+\.\d{10}$/');
});

test('no precision loss in multi-decimal operations', function () {
    $service = ($this->createService)();

    // Use a value with many decimals
    $quantity = '100.1234567890123456789';
    $result = $service->convert($quantity, 'kg', 'kg', 19);

    // Should preserve all decimals
    expect($result)->toBe('100.1234567890123456789');
});
