<?php

declare(strict_types=1);

use Nexus\Erp\Core\Models\Tenant;
use Nexus\Erp\Core\Models\User;
use Nexus\Erp\SettingsManagement\Facades\Settings;
use Nexus\Erp\SettingsManagement\Models\Setting;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    
    $this->tenant = Tenant::factory()->create([
        'name' => 'Test Tenant',
        'status' => 'active',
    ]);

    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
});

test('resolves setting from system level', function () {
    Setting::create([
        'key' => 'app.name',
        'value' => 'System App',
        'type' => 'string',
        'scope' => 'system',
        'tenant_id' => null,
    ]);

    $value = Settings::get('app.name', null, null, $this->tenant->id);
    expect($value)->toBe('System App');
});

test('resolves setting from tenant level overriding system', function () {
    // System level
    Setting::create([
        'key' => 'app.name',
        'value' => 'System App',
        'type' => 'string',
        'scope' => 'system',
        'tenant_id' => null,
    ]);

    // Tenant level (should override)
    Setting::create([
        'key' => 'app.name',
        'value' => 'Tenant App',
        'type' => 'string',
        'scope' => 'tenant',
        'tenant_id' => $this->tenant->id,
    ]);

    $value = Settings::get('app.name', null, null, $this->tenant->id);
    expect($value)->toBe('Tenant App');
});

test('resolves setting from module level overriding tenant', function () {
    // System level
    Setting::create([
        'key' => 'threshold',
        'value' => '10',
        'type' => 'integer',
        'scope' => 'system',
        'tenant_id' => null,
    ]);

    // Tenant level
    Setting::create([
        'key' => 'threshold',
        'value' => '20',
        'type' => 'integer',
        'scope' => 'tenant',
        'tenant_id' => $this->tenant->id,
    ]);

    // Module level (should override)
    Setting::create([
        'key' => 'threshold',
        'value' => '30',
        'type' => 'integer',
        'scope' => 'module',
        'tenant_id' => $this->tenant->id,
        'module_name' => 'inventory',
    ]);

    $value = Settings::get('threshold', null, null, $this->tenant->id, 'inventory');
    expect($value)->toBe(30);
});

test('resolves setting from user level overriding all others', function () {
    // System level
    Setting::create([
        'key' => 'ui.theme',
        'value' => 'light',
        'type' => 'string',
        'scope' => 'system',
        'tenant_id' => null,
    ]);

    // Tenant level
    Setting::create([
        'key' => 'ui.theme',
        'value' => 'dark',
        'type' => 'string',
        'scope' => 'tenant',
        'tenant_id' => $this->tenant->id,
    ]);

    // User level (should override)
    Setting::create([
        'key' => 'ui.theme',
        'value' => 'blue',
        'type' => 'string',
        'scope' => 'user',
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
    ]);

    $value = Settings::get('ui.theme', null, null, $this->tenant->id, null, $this->user->id);
    expect($value)->toBe('blue');
});

test('returns default value when setting not found', function () {
    $value = Settings::get('nonexistent.key', 'default value', null, $this->tenant->id);
    expect($value)->toBe('default value');
});

test('caches setting value on first read', function () {
    Setting::create([
        'key' => 'cached.setting',
        'value' => 'cached value',
        'type' => 'string',
        'scope' => 'tenant',
        'tenant_id' => $this->tenant->id,
    ]);

    // First read should cache
    $value = Settings::get('cached.setting', null, null, $this->tenant->id);
    expect($value)->toBe('cached value');

    // Verify it's in cache
    $cacheKey = 'settings:cached.setting:tenant:t' . $this->tenant->id;
    $cached = Cache::get($cacheKey);
    expect($cached)->toBe('cached value');
});

test('invalidates cache when setting is updated', function () {
    $setting = Setting::create([
        'key' => 'invalidate.test',
        'value' => 'old value',
        'type' => 'string',
        'scope' => 'tenant',
        'tenant_id' => $this->tenant->id,
    ]);

    // Read to cache
    $value = Settings::get('invalidate.test', null, null, $this->tenant->id);
    expect($value)->toBe('old value');

    // Update setting
    Settings::set('invalidate.test', 'new value', 'string', 'tenant', [], $this->tenant->id);

    // Cache should be invalidated, new read should get new value
    $value = Settings::get('invalidate.test', null, null, $this->tenant->id);
    expect($value)->toBe('new value');
});

test('can warm cache for multiple settings', function () {
    Setting::create([
        'key' => 'warm1',
        'value' => 'value1',
        'type' => 'string',
        'scope' => 'tenant',
        'tenant_id' => $this->tenant->id,
    ]);

    Setting::create([
        'key' => 'warm2',
        'value' => 'value2',
        'type' => 'string',
        'scope' => 'tenant',
        'tenant_id' => $this->tenant->id,
    ]);

    $count = Settings::warmCache('tenant', $this->tenant->id);
    expect($count)->toBeGreaterThanOrEqual(2);

    // Verify settings are cached
    $cacheKey1 = 'settings:warm1:tenant:t' . $this->tenant->id;
    $cacheKey2 = 'settings:warm2:tenant:t' . $this->tenant->id;
    
    expect(Cache::has($cacheKey1))->toBeTrue();
    expect(Cache::has($cacheKey2))->toBeTrue();
});

test('encrypts sensitive settings', function () {
    Settings::set('api.key', 'secret-key-123', 'encrypted', 'tenant', [], $this->tenant->id);

    $setting = Setting::where('key', 'api.key')
        ->where('tenant_id', $this->tenant->id)
        ->first();

    // Value in database should be encrypted (different from original)
    expect($setting->value)->not->toBe('secret-key-123');

    // But when retrieved through service, should be decrypted
    $value = Settings::get('api.key', null, null, $this->tenant->id);
    expect($value)->toBe('secret-key-123');
});

test('tracks setting changes in history', function () {
    $setting = Setting::create([
        'key' => 'track.test',
        'value' => 'initial',
        'type' => 'string',
        'scope' => 'tenant',
        'tenant_id' => $this->tenant->id,
    ]);

    // Update the setting
    Settings::set('track.test', 'updated', 'string', 'tenant', [], $this->tenant->id);

    // Check history was created
    expect($setting->history()->count())->toBeGreaterThan(0);
    
    $history = $setting->history()->latest('changed_at')->first();
    expect($history->action)->toBe('updated');
    expect($history->new_value)->toBe('updated');
});

test('handles boolean type correctly', function () {
    Settings::set('feature.enabled', true, 'boolean', 'tenant', [], $this->tenant->id);
    
    $value = Settings::get('feature.enabled', null, null, $this->tenant->id);
    expect($value)->toBeTrue();

    Settings::set('feature.enabled', false, 'boolean', 'tenant', [], $this->tenant->id);
    
    $value = Settings::get('feature.enabled', null, null, $this->tenant->id);
    expect($value)->toBeFalse();
});

test('handles array type correctly', function () {
    $array = ['option1', 'option2', 'option3'];
    Settings::set('list.options', $array, 'array', 'tenant', [], $this->tenant->id);
    
    $value = Settings::get('list.options', null, null, $this->tenant->id);
    expect($value)->toBe($array);
});

test('handles json type correctly', function () {
    $json = ['nested' => ['key' => 'value'], 'array' => [1, 2, 3]];
    Settings::set('config.json', $json, 'json', 'tenant', [], $this->tenant->id);
    
    $value = Settings::get('config.json', null, null, $this->tenant->id);
    expect($value)->toBe($json);
});
