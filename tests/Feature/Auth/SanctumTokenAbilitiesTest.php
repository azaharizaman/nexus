<?php

declare(strict_types=1);

use App\Domains\Core\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
});

test('token with tenant read ability', function () {
    Sanctum::actingAs($this->user, ['tenant:read']);

    expect(auth()->user()->tokenCan('tenant:read'))->toBeTrue();
    expect(auth()->user()->tokenCan('tenant:write'))->toBeFalse();
    expect(auth()->user()->tokenCan('tenant:delete'))->toBeFalse();
});

test('token with multiple abilities', function () {
    Sanctum::actingAs($this->user, ['tenant:read', 'tenant:write', 'user:read']);

    expect(auth()->user()->tokenCan('tenant:read'))->toBeTrue();
    expect(auth()->user()->tokenCan('tenant:write'))->toBeTrue();
    expect(auth()->user()->tokenCan('user:read'))->toBeTrue();
    expect(auth()->user()->tokenCan('tenant:delete'))->toBeFalse();
});

test('token with inventory abilities', function () {
    Sanctum::actingAs($this->user, ['inventory:read', 'inventory:write']);

    expect(auth()->user()->tokenCan('inventory:read'))->toBeTrue();
    expect(auth()->user()->tokenCan('inventory:write'))->toBeTrue();
    expect(auth()->user()->tokenCan('warehouse:read'))->toBeFalse();
});

test('token with sales abilities', function () {
    Sanctum::actingAs($this->user, ['customer:read', 'order:read', 'quotation:write']);

    expect(auth()->user()->tokenCan('customer:read'))->toBeTrue();
    expect(auth()->user()->tokenCan('order:read'))->toBeTrue();
    expect(auth()->user()->tokenCan('quotation:write'))->toBeTrue();
    expect(auth()->user()->tokenCan('order:write'))->toBeFalse();
});

test('token with purchasing abilities', function () {
    Sanctum::actingAs($this->user, ['vendor:read', 'purchase:write']);

    expect(auth()->user()->tokenCan('vendor:read'))->toBeTrue();
    expect(auth()->user()->tokenCan('purchase:write'))->toBeTrue();
    expect(auth()->user()->tokenCan('goods-receipt:write'))->toBeFalse();
});

test('token with accounting abilities', function () {
    Sanctum::actingAs($this->user, ['accounting:read', 'reports:read']);

    expect(auth()->user()->tokenCan('accounting:read'))->toBeTrue();
    expect(auth()->user()->tokenCan('reports:read'))->toBeTrue();
    expect(auth()->user()->tokenCan('accounting:write'))->toBeFalse();
});

test('token with backoffice abilities', function () {
    Sanctum::actingAs($this->user, ['company:read', 'office:read', 'department:write']);

    expect(auth()->user()->tokenCan('company:read'))->toBeTrue();
    expect(auth()->user()->tokenCan('office:read'))->toBeTrue();
    expect(auth()->user()->tokenCan('department:write'))->toBeTrue();
    expect(auth()->user()->tokenCan('staff:write'))->toBeFalse();
});

test('token with read only api ability', function () {
    Sanctum::actingAs($this->user, ['api:read-only']);

    expect(auth()->user()->tokenCan('api:read-only'))->toBeTrue();
    expect(auth()->user()->tokenCan('tenant:write'))->toBeFalse();
    expect(auth()->user()->tokenCan('api:full-access'))->toBeFalse();
});

test('token with full access api ability', function () {
    Sanctum::actingAs($this->user, ['api:full-access']);

    expect(auth()->user()->tokenCan('api:full-access'))->toBeTrue();
    // Note: 'api:full-access' is just one ability, not wildcard
    expect(auth()->user()->tokenCan('tenant:read'))->toBeFalse();
});

test('abilities follow consistent pattern', function () {
    $abilities = [
        'tenant:read',
        'tenant:write',
        'user:read',
        'inventory:read',
        'customer:write',
        'vendor:delete',
    ];

    Sanctum::actingAs($this->user, $abilities);

    foreach ($abilities as $ability) {
        expect(auth()->user()->tokenCan($ability))->toBeTrue();
        // Verify pattern: domain:action
        expect($ability)->toContain(':');
    }
});

test('token without ability is denied', function () {
    Sanctum::actingAs($this->user, ['tenant:read']);

    expect(auth()->user()->tokenCan('tenant:write'))->toBeFalse();
    expect(auth()->user()->tokenCan('user:read'))->toBeFalse();
    expect(auth()->user()->tokenCan('inventory:write'))->toBeFalse();
});

test('token with empty abilities', function () {
    Sanctum::actingAs($this->user, []);

    expect(auth()->user()->tokenCan('tenant:read'))->toBeFalse();
    expect(auth()->user()->tokenCan('tenant:write'))->toBeFalse();
});

test('token with settings abilities', function () {
    Sanctum::actingAs($this->user, ['settings:read']);

    expect(auth()->user()->tokenCan('settings:read'))->toBeTrue();
    expect(auth()->user()->tokenCan('settings:write'))->toBeFalse();
});

test('token with warehouse and stock abilities', function () {
    Sanctum::actingAs($this->user, ['warehouse:read', 'stock:read', 'stock:write']);

    expect(auth()->user()->tokenCan('warehouse:read'))->toBeTrue();
    expect(auth()->user()->tokenCan('stock:read'))->toBeTrue();
    expect(auth()->user()->tokenCan('stock:write'))->toBeTrue();
    expect(auth()->user()->tokenCan('warehouse:write'))->toBeFalse();
});

test('ability checks are case sensitive', function () {
    Sanctum::actingAs($this->user, ['tenant:read']);

    expect(auth()->user()->tokenCan('tenant:read'))->toBeTrue();
    expect(auth()->user()->tokenCan('Tenant:Read'))->toBeFalse();
    expect(auth()->user()->tokenCan('TENANT:READ'))->toBeFalse();
});

test('creating tokens with various ability combinations', function () {
    // Read-only token
    $readToken = $this->user->createToken('read-only', [
        'tenant:read',
        'user:read',
        'inventory:read',
    ]);

    expect($readToken->accessToken->abilities)->toHaveCount(3);

    // Write token
    $writeToken = $this->user->createToken('write-access', [
        'tenant:write',
        'inventory:write',
        'customer:write',
    ]);

    expect($writeToken->accessToken->abilities)->toHaveCount(3);

    // Admin token
    $adminToken = $this->user->createToken('admin-access', ['*']);

    expect($adminToken->accessToken->abilities)->toBe(['*']);
});
