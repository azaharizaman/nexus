<?php

declare(strict_types=1);

use Azaharizaman\Erp\Core\Models\Tenant;
use Azaharizaman\Erp\Core\Models\User;
use Azaharizaman\Erp\SettingsManagement\Facades\Settings;
use Azaharizaman\Erp\SettingsManagement\Models\Setting;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // Create a tenant
    $this->tenant = Tenant::factory()->create([
        'name' => 'Test Tenant',
        'status' => 'active',
    ]);

    // Create a user with admin role
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
    ]);
    
    // Assign admin role (assuming Spatie permissions)
    $this->user->assignRole('admin');
});

test('can create a setting via API', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/settings', [
            'key' => 'test.setting',
            'value' => 'test value',
            'type' => 'string',
            'scope' => 'tenant',
        ]);

    $response->assertCreated();
    expect($response->json('data.key'))->toBe('test.setting');
    expect($response->json('data.value'))->toBe('test value');
});

test('can retrieve a setting via API', function () {
    Setting::create([
        'key' => 'app.name',
        'value' => 'My App',
        'type' => 'string',
        'scope' => 'tenant',
        'tenant_id' => $this->tenant->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/settings/app.name?scope=tenant');

    $response->assertOk();
    expect($response->json('data.value'))->toBe('My App');
});

test('can list all settings for tenant', function () {
    Setting::create([
        'key' => 'setting1',
        'value' => 'value1',
        'type' => 'string',
        'scope' => 'tenant',
        'tenant_id' => $this->tenant->id,
    ]);

    Setting::create([
        'key' => 'setting2',
        'value' => 'value2',
        'type' => 'string',
        'scope' => 'tenant',
        'tenant_id' => $this->tenant->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/settings?scope=tenant');

    $response->assertOk();
    expect($response->json('meta.count'))->toBeGreaterThanOrEqual(2);
});

test('can update a setting via API', function () {
    $setting = Setting::create([
        'key' => 'app.name',
        'value' => 'Old Name',
        'type' => 'string',
        'scope' => 'tenant',
        'tenant_id' => $this->tenant->id,
    ]);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/v1/settings/{$setting->key}", [
            'value' => 'New Name',
            'scope' => 'tenant',
        ]);

    $response->assertOk();
    expect($response->json('data.value'))->toBe('New Name');
});

test('can delete a setting via API', function () {
    $setting = Setting::create([
        'key' => 'temp.setting',
        'value' => 'temp value',
        'type' => 'string',
        'scope' => 'tenant',
        'tenant_id' => $this->tenant->id,
    ]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/settings/{$setting->key}?scope=tenant");

    $response->assertNoContent();
    expect(Setting::where('key', 'temp.setting')->count())->toBe(0);
});

test('can bulk update settings via API', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/settings/bulk', [
            'scope' => 'tenant',
            'settings' => [
                [
                    'key' => 'bulk.setting1',
                    'value' => 'value1',
                    'type' => 'string',
                ],
                [
                    'key' => 'bulk.setting2',
                    'value' => 'value2',
                    'type' => 'string',
                ],
            ],
        ]);

    $response->assertOk();
    expect($response->json('data.success_count'))->toBe(2);
    expect(Setting::where('key', 'bulk.setting1')->exists())->toBeTrue();
    expect(Setting::where('key', 'bulk.setting2')->exists())->toBeTrue();
});

test('validates setting key format', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/settings', [
            'key' => 'invalid key with spaces',
            'value' => 'test',
            'type' => 'string',
            'scope' => 'tenant',
        ]);

    $response->assertStatus(422);
    expect($response->json('errors.key'))->not->toBeNull();
});

test('validates setting type', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/settings', [
            'key' => 'test.setting',
            'value' => 'test',
            'type' => 'invalid_type',
            'scope' => 'tenant',
        ]);

    $response->assertStatus(422);
    expect($response->json('errors.type'))->not->toBeNull();
});

test('requires authentication for API access', function () {
    $response = $this->postJson('/api/v1/settings', [
        'key' => 'test.setting',
        'value' => 'test',
        'type' => 'string',
        'scope' => 'tenant',
    ]);

    $response->assertUnauthorized();
});

test('prevents access to other tenants settings', function () {
    // Create another tenant and setting
    $otherTenant = Tenant::factory()->create(['name' => 'Other Tenant']);
    Setting::create([
        'key' => 'other.setting',
        'value' => 'secret',
        'type' => 'string',
        'scope' => 'tenant',
        'tenant_id' => $otherTenant->id,
    ]);

    // Try to access as current user (different tenant)
    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/settings/other.setting?scope=tenant');

    // Should not find the setting (tenant isolation)
    $response->assertNotFound();
});

test('can export settings as JSON', function () {
    Setting::create([
        'key' => 'export.test1',
        'value' => 'value1',
        'type' => 'string',
        'scope' => 'tenant',
        'tenant_id' => $this->tenant->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/settings/export?scope=tenant&format=json');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/json');
});

test('can export settings as CSV', function () {
    Setting::create([
        'key' => 'export.test1',
        'value' => 'value1',
        'type' => 'string',
        'scope' => 'tenant',
        'tenant_id' => $this->tenant->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/settings/export?scope=tenant&format=csv');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
});
