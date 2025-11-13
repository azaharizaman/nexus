<?php

declare(strict_types=1);

use Nexus\Erp\SerialNumbering\Enums\ResetPeriod;
use Nexus\Erp\SerialNumbering\Models\Sequence;
use Carbon\Carbon;

test('sequence model has correct fillable attributes', function () {
    $sequence = new Sequence();
    
    expect($sequence->getFillable())->toContain('tenant_id', 'sequence_name', 'pattern');
});

test('sequence model casts reset_period to enum', function () {
    $sequence = new Sequence([
        'reset_period' => 'yearly',
    ]);
    
    expect($sequence->reset_period)->toBeInstanceOf(ResetPeriod::class);
    expect($sequence->reset_period)->toBe(ResetPeriod::YEARLY);
});

test('shouldReset returns false for NEVER reset period', function () {
    $sequence = new Sequence([
        'reset_period' => ResetPeriod::NEVER,
        'last_reset_at' => now()->subYear(),
    ]);
    
    expect($sequence->shouldReset())->toBeFalse();
});

test('shouldReset returns false when last_reset_at is null', function () {
    $sequence = new Sequence([
        'reset_period' => ResetPeriod::YEARLY,
        'last_reset_at' => null,
    ]);
    
    expect($sequence->shouldReset())->toBeFalse();
});

test('shouldReset returns true for daily reset when last reset was yesterday', function () {
    $sequence = new Sequence([
        'reset_period' => ResetPeriod::DAILY,
        'last_reset_at' => now()->subDay(),
    ]);
    
    expect($sequence->shouldReset())->toBeTrue();
});

test('shouldReset returns false for daily reset when last reset was today', function () {
    $sequence = new Sequence([
        'reset_period' => ResetPeriod::DAILY,
        'last_reset_at' => now(),
    ]);
    
    expect($sequence->shouldReset())->toBeFalse();
});

test('shouldReset returns true for monthly reset when last reset was last month', function () {
    $sequence = new Sequence([
        'reset_period' => ResetPeriod::MONTHLY,
        'last_reset_at' => now()->subMonth(),
    ]);
    
    expect($sequence->shouldReset())->toBeTrue();
});

test('shouldReset returns false for monthly reset when last reset was this month', function () {
    $sequence = new Sequence([
        'reset_period' => ResetPeriod::MONTHLY,
        'last_reset_at' => now(),
    ]);
    
    expect($sequence->shouldReset())->toBeFalse();
});

test('shouldReset returns true for yearly reset when last reset was last year', function () {
    $sequence = new Sequence([
        'reset_period' => ResetPeriod::YEARLY,
        'last_reset_at' => now()->subYear(),
    ]);
    
    expect($sequence->shouldReset())->toBeTrue();
});

test('shouldReset returns false for yearly reset when last reset was this year', function () {
    $sequence = new Sequence([
        'reset_period' => ResetPeriod::YEARLY,
        'last_reset_at' => now(),
    ]);
    
    expect($sequence->shouldReset())->toBeFalse();
});

test('ResetPeriod enum has correct values', function () {
    expect(ResetPeriod::values())->toBe(['never', 'daily', 'monthly', 'yearly']);
});

test('ResetPeriod enum has correct labels', function () {
    expect(ResetPeriod::NEVER->label())->toBe('Never');
    expect(ResetPeriod::DAILY->label())->toBe('Daily');
    expect(ResetPeriod::MONTHLY->label())->toBe('Monthly');
    expect(ResetPeriod::YEARLY->label())->toBe('Yearly');
});
