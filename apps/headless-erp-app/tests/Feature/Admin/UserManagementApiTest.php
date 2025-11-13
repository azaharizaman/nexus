<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Contracts\PermissionServiceContract;
use Nexus\Erp\Core\Enums\UserStatus;
use Nexus\Erp\Core\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->admin = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_admin' => true,
    ]);

    // Give admin necessary permissions
    $permissionService = app(PermissionServiceContract::class);
    $permissionService->createPermission('view-users');
    $permissionService->createPermission('create-users');
    $permissionService->createPermission('update-users');
    $permissionService->createPermission('delete-users');
    $permissionService->createPermission('suspend-users');

    $adminRole = $permissionService->createRole('admin', $this->tenant->id);
    // Use batch permission assignment for efficiency
    $permissions = ['view-users', 'create-users', 'update-users', 'delete-users', 'suspend-users'];
    $permissionService->givePermissionsToRole($adminRole, $permissions);
    $permissionService->assignRole($this->admin, $adminRole);

    // Create test token for admin
    $this->token = $this->admin->createToken('test-token')->plainTextToken;
});

test('admin can list users', function () {
    // Create some users
    User::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/admin/users');

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            '*' => ['id', 'name', 'email', 'status'],
        ],
    ]);
});

test('admin can create new user', function () {
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/admin/users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'tenant_id' => $this->tenant->id,
        ]);

    $response->assertCreated();
    $response->assertJsonFragment(['email' => 'newuser@example.com']);

    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
        'tenant_id' => $this->tenant->id,
    ]);
});

test('admin can suspend user', function () {
    $user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'status' => UserStatus::ACTIVE,
    ]);

    $response = $this->withToken($this->token)
        ->postJson("/api/v1/admin/users/{$user->id}/suspend", [
            'reason' => 'Policy violation',
        ]);

    $response->assertOk();

    $user->refresh();
    expect($user->status)->toBe(UserStatus::SUSPENDED);
});

test('admin can unlock user', function () {
    $user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'locked_until' => now()->addHour(),
        'failed_login_attempts' => 5,
    ]);

    $response = $this->withToken($this->token)
        ->postJson("/api/v1/admin/users/{$user->id}/unlock");

    $response->assertOk();

    $user->refresh();
    expect($user->locked_until)->toBeNull();
    expect($user->failed_login_attempts)->toBe(0);
});

test('admin cannot manage users from different tenant', function () {
    $otherTenant = Tenant::factory()->create();
    $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

    $response = $this->withToken($this->token)
        ->getJson("/api/v1/admin/users/{$otherUser->id}");

    $response->assertForbidden();
});

test('requires authentication for user management', function () {
    $response = $this->getJson('/api/v1/admin/users');

    $response->assertUnauthorized();
});
