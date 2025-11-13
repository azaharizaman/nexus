<?php

declare(strict_types=1);

use Nexus\Erp\SettingsManagement\Models\Setting;
use Nexus\Erp\SettingsManagement\Services\SettingsService;
use Illuminate\Support\Facades\Crypt;

test('can cast string value correctly', function () {
    $service = app(SettingsService::class);
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('castValue');
    $method->setAccessible(true);

    $result = $method->invoke($service, 'hello', Setting::TYPE_STRING);
    expect($result)->toBe('hello');
});

test('can cast integer value correctly', function () {
    $service = app(SettingsService::class);
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('castValue');
    $method->setAccessible(true);

    $result = $method->invoke($service, '42', Setting::TYPE_INTEGER);
    expect($result)->toBe(42);
});

test('can cast boolean value correctly', function () {
    $service = app(SettingsService::class);
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('castValue');
    $method->setAccessible(true);

    $result = $method->invoke($service, '1', Setting::TYPE_BOOLEAN);
    expect($result)->toBeTrue();

    $result = $method->invoke($service, '0', Setting::TYPE_BOOLEAN);
    expect($result)->toBeFalse();
});

test('can cast array value correctly', function () {
    $service = app(SettingsService::class);
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('castValue');
    $method->setAccessible(true);

    $array = ['key1' => 'value1', 'key2' => 'value2'];
    $result = $method->invoke($service, json_encode($array), Setting::TYPE_ARRAY);
    expect($result)->toBe($array);
});

test('can cast json value correctly', function () {
    $service = app(SettingsService::class);
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('castValue');
    $method->setAccessible(true);

    $json = ['nested' => ['key' => 'value']];
    $result = $method->invoke($service, json_encode($json), Setting::TYPE_JSON);
    expect($result)->toBe($json);
});

test('can encrypt and decrypt values', function () {
    $service = app(SettingsService::class);
    $reflection = new ReflectionClass($service);
    
    $encryptMethod = $reflection->getMethod('encryptValue');
    $encryptMethod->setAccessible(true);
    
    $decryptMethod = $reflection->getMethod('decryptValue');
    $decryptMethod->setAccessible(true);

    $originalValue = 'sensitive-api-key';
    $encrypted = $encryptMethod->invoke($service, $originalValue);
    $decrypted = $decryptMethod->invoke($service, $encrypted);

    expect($encrypted)->not->toBe($originalValue);
    expect($decrypted)->toBe($originalValue);
});

test('can cast to storage format for string', function () {
    $service = app(SettingsService::class);
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('castToStorage');
    $method->setAccessible(true);

    $result = $method->invoke($service, 'test', Setting::TYPE_STRING);
    expect($result)->toBe('test');
});

test('can cast to storage format for boolean', function () {
    $service = app(SettingsService::class);
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('castToStorage');
    $method->setAccessible(true);

    $result = $method->invoke($service, true, Setting::TYPE_BOOLEAN);
    expect($result)->toBe('1');

    $result = $method->invoke($service, false, Setting::TYPE_BOOLEAN);
    expect($result)->toBe('0');
});

test('can cast to storage format for array', function () {
    $service = app(SettingsService::class);
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('castToStorage');
    $method->setAccessible(true);

    $array = ['key' => 'value'];
    $result = $method->invoke($service, $array, Setting::TYPE_ARRAY);
    expect($result)->toBe(json_encode($array));
});

test('generates correct cache keys', function () {
    $service = app(SettingsService::class);
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('getCacheKey');
    $method->setAccessible(true);

    $key = $method->invoke($service, 'email.smtp.host', 'tenant', 1);
    expect($key)->toBe('settings:email.smtp.host:tenant:t1');

    $key = $method->invoke($service, 'app.name', 'system');
    expect($key)->toBe('settings:app.name:system');

    $key = $method->invoke($service, 'inventory.threshold', 'module', 1, 'inventory');
    expect($key)->toBe('settings:inventory.threshold:module:t1:minventory');
});

test('gets correct scope hierarchy', function () {
    $service = app(SettingsService::class);
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('getScopesForHierarchy');
    $method->setAccessible(true);

    // User scope should include all levels
    $scopes = $method->invoke($service, 'user');
    expect($scopes)->toBe(['user', 'module', 'tenant', 'system']);

    // Tenant scope should include tenant and system
    $scopes = $method->invoke($service, 'tenant');
    expect($scopes)->toBe(['tenant', 'system']);

    // System scope should only include system
    $scopes = $method->invoke($service, 'system');
    expect($scopes)->toBe(['system']);
});
