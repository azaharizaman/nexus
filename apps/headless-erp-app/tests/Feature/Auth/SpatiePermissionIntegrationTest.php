<?php

declare(strict_types=1);

use App\Models\User;
use Nexus\TenancyManagement\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
});

test('user can be assigned a role', function () {
    $role = Role::create(['name' => 'admin', 'team_id' => $this->tenant->id]);

    setPermissionsTeamId($this->tenant->id);
    $this->user->assignRole($role);

    expect($this->user->hasRole('admin'))->toBeTrue();
    expect($this->user->roles)->toHaveCount(1);
    expect($this->user->roles->first()->name)->toBe('admin');
});

test('user can have multiple roles', function () {
    $adminRole = Role::create(['name' => 'admin', 'team_id' => $this->tenant->id]);
    $managerRole = Role::create(['name' => 'manager', 'team_id' => $this->tenant->id]);

    setPermissionsTeamId($this->tenant->id);
    $this->user->assignRole([$adminRole, $managerRole]);

    expect($this->user->hasRole('admin'))->toBeTrue();
    expect($this->user->hasRole('manager'))->toBeTrue();
    expect($this->user->roles)->toHaveCount(2);
});

test('user can be assigned direct permissions', function () {
    $permission = Permission::create(['name' => 'edit users']);

    setPermissionsTeamId($this->tenant->id);
    $this->user->givePermissionTo($permission);

    expect($this->user->hasPermissionTo('edit users'))->toBeTrue();
    expect($this->user->permissions)->toHaveCount(1);
});

test('role can have permissions', function () {
    $role = Role::create(['name' => 'admin', 'team_id' => $this->tenant->id]);
    $permission = Permission::create(['name' => 'edit users']);

    $role->givePermissionTo($permission);

    expect($role->hasPermissionTo('edit users'))->toBeTrue();
    expect($role->permissions)->toHaveCount(1);
});

test('user inherits permissions from role', function () {
    $role = Role::create(['name' => 'admin', 'team_id' => $this->tenant->id]);
    $permission = Permission::create(['name' => 'edit users']);

    $role->givePermissionTo($permission);
    setPermissionsTeamId($this->tenant->id);
    $this->user->assignRole($role);

    expect($this->user->hasPermissionTo('edit users'))->toBeTrue();
});

test('roles are scoped to tenant', function () {
    $tenant2 = Tenant::factory()->create();
    $user2 = User::factory()->create([
        'tenant_id' => $tenant2->id,
    ]);

    // Create roles with unique names for different tenants
    $role1 = Role::create(['name' => 'tenant1-admin', 'team_id' => $this->tenant->id]);
    $role2 = Role::create(['name' => 'tenant2-admin', 'team_id' => $tenant2->id]);

    setPermissionsTeamId($this->tenant->id);
    $this->user->assignRole($role1);

    setPermissionsTeamId($tenant2->id);
    $user2->assignRole($role2);

    setPermissionsTeamId($this->tenant->id);
    expect($this->user->hasRole('tenant1-admin'))->toBeTrue();
    expect($this->user->hasRole('tenant2-admin'))->toBeFalse();

    setPermissionsTeamId($tenant2->id);
    expect($user2->hasRole('tenant2-admin'))->toBeTrue();
    expect($user2->hasRole('tenant1-admin'))->toBeFalse();

    // Both users have different roles from different tenants
    expect($this->user->roles->first()->id)->not->toBe($user2->roles->first()->id);
});

test('permission team id is correctly set', function () {
    expect($this->user->getPermissionTeamId())->toBe($this->tenant->id);
});

test('user cannot access roles from different tenant', function () {
    $tenant2 = Tenant::factory()->create();
    $role = Role::create(['name' => 'other-tenant-admin', 'team_id' => $tenant2->id]);

    // User from tenant1 tries to check role from tenant2
    setPermissionsTeamId($this->tenant->id);

    // The user should not have access to roles from different tenant
    expect($this->user->hasRole('other-tenant-admin'))->toBeFalse();
});

test('permission caching is configured', function () {
    $cacheExpiration = config('permission.cache.expiration_time');
    $cacheStore = config('permission.cache.store');

    expect($cacheExpiration)->not->toBeNull();
    expect($cacheStore)->toBe('default');
});

test('teams feature is enabled', function () {
    $teamsEnabled = config('permission.teams');

    expect($teamsEnabled)->toBeTrue();
});

test('team foreign key is set to tenant_id', function () {
    $teamForeignKey = config('permission.column_names.team_foreign_key');

    expect($teamForeignKey)->toBe('tenant_id');
});

test('permission tables exist in database', function () {
    $tableNames = config('permission.table_names');

    expect(Schema::hasTable($tableNames['permissions']))->toBeTrue();
    expect(Schema::hasTable($tableNames['roles']))->toBeTrue();
    expect(Schema::hasTable($tableNames['model_has_permissions']))->toBeTrue();
    expect(Schema::hasTable($tableNames['model_has_roles']))->toBeTrue();
    expect(Schema::hasTable($tableNames['role_has_permissions']))->toBeTrue();
});

test('permission tables have team foreign key column', function () {
    $tableNames = config('permission.table_names');
    $teamForeignKey = config('permission.column_names.team_foreign_key');

    expect(Schema::hasColumn($tableNames['roles'], $teamForeignKey))->toBeTrue();
    expect(Schema::hasColumn($tableNames['model_has_permissions'], $teamForeignKey))->toBeTrue();
    expect(Schema::hasColumn($tableNames['model_has_roles'], $teamForeignKey))->toBeTrue();
});

test('user can check permission via gate', function () {
    $role = Role::create(['name' => 'admin', 'team_id' => $this->tenant->id]);
    $permission = Permission::create(['name' => 'edit users']);

    $role->givePermissionTo($permission);
    setPermissionsTeamId($this->tenant->id);
    $this->user->assignRole($role);

    expect($this->user->can('edit users'))->toBeTrue();
});

test('user cannot check permission they do not have', function () {
    $permission = Permission::create(['name' => 'delete users']);

    expect($this->user->can('delete users'))->toBeFalse();
});

test('role can be removed from user', function () {
    $role = Role::create(['name' => 'admin', 'team_id' => $this->tenant->id]);

    setPermissionsTeamId($this->tenant->id);
    $this->user->assignRole($role);
    expect($this->user->hasRole('admin'))->toBeTrue();

    $this->user->removeRole($role);
    expect($this->user->hasRole('admin'))->toBeFalse();
});

test('permission can be revoked from user', function () {
    $permission = Permission::create(['name' => 'edit users']);

    setPermissionsTeamId($this->tenant->id);
    $this->user->givePermissionTo($permission);
    expect($this->user->hasPermissionTo('edit users'))->toBeTrue();

    $this->user->revokePermissionTo($permission);
    expect($this->user->hasPermissionTo('edit users'))->toBeFalse();
});

test('user has roles trait methods available', function () {
    expect(method_exists($this->user, 'assignRole'))->toBeTrue();
    expect(method_exists($this->user, 'hasRole'))->toBeTrue();
    expect(method_exists($this->user, 'givePermissionTo'))->toBeTrue();
    expect(method_exists($this->user, 'hasPermissionTo'))->toBeTrue();
    expect(method_exists($this->user, 'getPermissionTeamId'))->toBeTrue();
});
