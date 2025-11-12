<?php

declare(strict_types=1);

use App\Actions\Permission\AssignRoleToUserAction;
use App\Actions\Permission\CreateRoleAction;
use App\Models\User;
use App\Support\Contracts\PermissionServiceContract;
use Azaharizaman\Erp\Core\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create role with permissions', function () {
    $tenant = Tenant::factory()->create();
    $permissionService = app(PermissionServiceContract::class);

    // Create permissions
    $permission1 = $permissionService->createPermission('test-permission-1');
    $permission2 = $permissionService->createPermission('test-permission-2');

    // Create role
    $action = app(CreateRoleAction::class);
    $role = $action->handle('test-role', ['test-permission-1', 'test-permission-2'], $tenant->id);

    expect($role)->not->toBeNull();
    expect($role->name)->toBe('test-role');
    expect($role->team_id)->toBe($tenant->id);
    expect($role->permissions)->toHaveCount(2);
});

test('role creation validates tenant uniqueness', function () {
    $tenant = Tenant::factory()->create();

    // Create first role
    $action = app(CreateRoleAction::class);
    $role1 = $action->handle('duplicate-role', [], $tenant->id);

    expect($role1)->not->toBeNull();

    // Try to create duplicate role for same tenant
    $action->handle('duplicate-role', [], $tenant->id);
})->throws(\Illuminate\Validation\ValidationException::class);

test('can assign role to user', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $permissionService = app(PermissionServiceContract::class);

    // Create role
    $role = $permissionService->createRole('test-role', $tenant->id);

    // Assign role to user
    $action = app(AssignRoleToUserAction::class);
    $action->handle($user, $role);

    expect($permissionService->hasRole($user, 'test-role'))->toBeTrue();
});

test('cannot assign role from different tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    $user = User::factory()->create(['tenant_id' => $tenant1->id]);
    $permissionService = app(PermissionServiceContract::class);

    // Create role in tenant2
    $role = $permissionService->createRole('tenant2-role', $tenant2->id);

    // Try to assign to user in tenant1
    $action = app(AssignRoleToUserAction::class);
    $action->handle($user, $role);
})->throws(\Illuminate\Validation\ValidationException::class);

test('can assign global role to any user', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $permissionService = app(PermissionServiceContract::class);

    // Create global role (no tenant_id)
    $role = $permissionService->createRole('global-role', null);

    // Assign global role to user
    $action = app(AssignRoleToUserAction::class);
    $action->handle($user, $role);

    expect($permissionService->hasRole($user, 'global-role'))->toBeTrue();
});

test('permission cache is cleared after role assignment', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $permissionService = app(PermissionServiceContract::class);

    // Create role with permission
    $permission = $permissionService->createPermission('test-permission');
    $role = $permissionService->createRole('test-role', $tenant->id);
    $permissionService->givePermissionToRole($role, $permission);

    // User should not have permission yet
    expect($permissionService->hasPermissionTo($user, 'test-permission'))->toBeFalse();

    // Assign role
    $action = app(AssignRoleToUserAction::class);
    $action->handle($user, $role);

    // User should now have permission (cache was cleared)
    expect($permissionService->hasPermissionTo($user, 'test-permission'))->toBeTrue();
});
