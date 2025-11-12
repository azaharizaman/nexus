<?php

declare(strict_types=1);

use Azaharizaman\Erp\AuditLogging\Services\LogFormatterService;

/**
 * Unit Tests for LogFormatterService
 *
 * Tests sensitive field masking, actor detection, and data formatting.
 */
test('masks sensitive fields correctly', function () {
    $formatter = new LogFormatterService;

    $data = [
        'username' => 'john.doe',
        'password' => 'secret123',
        'email' => 'john@example.com',
        'api_key' => 'key-12345',
        'token' => 'bearer-token',
        'nested' => [
            'secret' => 'nested-secret',
            'public' => 'visible',
        ],
    ];

    $masked = $formatter->maskSensitiveFields($data);

    expect($masked['username'])->toBe('john.doe');
    expect($masked['email'])->toBe('john@example.com');
    expect($masked['password'])->toBe('[REDACTED]');
    expect($masked['api_key'])->toBe('[REDACTED]');
    expect($masked['token'])->toBe('[REDACTED]');
    expect($masked['nested']['secret'])->toBe('[REDACTED]');
    expect($masked['nested']['public'])->toBe('visible');
});

test('masks sensitive fields with custom field list', function () {
    $formatter = new LogFormatterService;

    $data = [
        'username' => 'john.doe',
        'custom_secret' => 'should-be-masked',
        'email' => 'john@example.com',
    ];

    $masked = $formatter->maskSensitiveFields($data, ['custom_secret']);

    expect($masked['username'])->toBe('john.doe');
    expect($masked['email'])->toBe('john@example.com');
    expect($masked['custom_secret'])->toBe('[REDACTED]');
});

test('gets actor information when user is authenticated', function () {
    // This test would require authentication mock
    // Placeholder for actual implementation
    expect(true)->toBeTrue();
})->skip('Requires authentication mock setup');

test('gets actor information for system processes', function () {
    $formatter = new LogFormatterService;

    // When not authenticated, should return system actor
    $actor = $formatter->getActor();

    expect($actor)->toHaveKey('type');
    expect($actor)->toHaveKey('id');
    expect($actor)->toHaveKey('name');
    expect($actor['type'])->toBe('system');
    expect($actor['id'])->toBeNull();
    expect($actor['name'])->toBe('System');
});

test('gets request context information', function () {
    $formatter = new LogFormatterService;

    $context = $formatter->getRequestContext();

    expect($context)->toHaveKey('ip_address');
    expect($context)->toHaveKey('user_agent');
    expect($context)->toHaveKey('request_id');

    // In CLI context
    expect($context['user_agent'])->toBe('CLI');
    expect($context['request_id'])->not->toBeEmpty();
});

test('generates default description for events', function () {
    $formatter = new LogFormatterService;

    // Use reflection to test protected method
    $reflection = new ReflectionClass($formatter);
    $method = $reflection->getMethod('generateDescription');
    $method->setAccessible(true);

    $model = new class
    {
        public function getKey()
        {
            return 123;
        }

        public function getTable()
        {
            return 'test_models';
        }
    };

    $description = $method->invoke($formatter, $model, 'created');
    expect($description)->toContain('was created');
    expect($description)->toContain('#123');

    $description = $method->invoke($formatter, $model, 'updated');
    expect($description)->toContain('was updated');

    $description = $method->invoke($formatter, $model, 'deleted');
    expect($description)->toContain('was deleted');
});

test('formats properties for storage', function () {
    $formatter = new LogFormatterService;

    $properties = [
        'status' => 'active',
        'password' => 'secret',
        'metadata' => [
            'token' => 'secret-token',
            'user_id' => 123,
        ],
    ];

    $formatted = $formatter->formatProperties($properties);

    expect($formatted['status'])->toBe('active');
    expect($formatted['password'])->toBe('[REDACTED]');
    expect($formatted['metadata']['token'])->toBe('[REDACTED]');
    expect($formatted['metadata']['user_id'])->toBe(123);
});

test('masks password variations case-insensitively', function () {
    $formatter = new LogFormatterService;

    $data = [
        'Password' => 'secret',
        'PASSWORD' => 'secret',
        'user_password' => 'secret',
        'password_hash' => 'hash',
    ];

    $masked = $formatter->maskSensitiveFields($data);

    expect($masked['Password'])->toBe('[REDACTED]');
    expect($masked['PASSWORD'])->toBe('[REDACTED]');
    expect($masked['user_password'])->toBe('[REDACTED]');
    expect($masked['password_hash'])->toBe('[REDACTED]');
});
