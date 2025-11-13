<?php

declare(strict_types=1);

namespace Nexus\Erp\Support\Services\Permission;

use Nexus\Erp\Support\Contracts\PermissionServiceContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Spatie Permission Service
 *
 * Adapter that wraps spatie/laravel-permission package functionality.
 * Implements PermissionServiceContract to decouple business logic from the package.
 */
class SpatiePermissionService implements PermissionServiceContract
{
    /**
     * Create a new role
     */
    public function createRole(string $name, string|int|null $teamId = null, string $guardName = 'web'): mixed
    {
        $attributes = [
            'name' => $name,
            'guard_name' => $guardName,
        ];

        if ($teamId !== null) {
            $attributes['team_id'] = $teamId;
        }

        return Role::create($attributes);
    }

    /**
     * Create a new permission
     */
    public function createPermission(string $name, string $guardName = 'web'): mixed
    {
        return Permission::create([
            'name' => $name,
            'guard_name' => $guardName,
        ]);
    }

    /**
     * Assign a role to a user
     */
    public function assignRole(Model $user, string|object $role): void
    {
        $user->assignRole($role);
    }

    /**
     * Assign multiple roles to a user
     */
    public function assignRoles(Model $user, array $roles): void
    {
        $user->assignRole($roles);
    }

    /**
     * Remove a role from a user
     */
    public function removeRole(Model $user, string|object $role): void
    {
        $user->removeRole($role);
    }

    /**
     * Check if a user has a specific role
     */
    public function hasRole(Model $user, string $role): bool
    {
        return $user->hasRole($role);
    }

    /**
     * Give a permission directly to a user
     */
    public function givePermissionTo(Model $user, string|object $permission): void
    {
        $user->givePermissionTo($permission);
    }

    /**
     * Revoke a permission from a user
     */
    public function revokePermissionTo(Model $user, string|object $permission): void
    {
        $user->revokePermissionTo($permission);
    }

    /**
     * Check if a user has a specific permission
     */
    public function hasPermissionTo(Model $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission);
    }

    /**
     * Give a permission to a role
     */
    public function givePermissionToRole(mixed $role, string|object $permission): void
    {
        $role->givePermissionTo($permission);
    }

    /**
     * Get all roles for a user
     */
    public function getUserRoles(Model $user): Collection
    {
        return $user->roles;
    }

    /**
     * Get all permissions for a user (direct and via roles)
     */
    public function getUserPermissions(Model $user): Collection
    {
        return $user->getAllPermissions();
    }

    /**
     * Set the current team/tenant context for permission checks
     */
    public function setPermissionsTeamId(string|int $teamId): void
    {
        setPermissionsTeamId($teamId);
    }

    /**
     * Get the current team/tenant context
     */
    public function getPermissionsTeamId(): string|int|null
    {
        return getPermissionsTeamId();
    }

    /**
     * Check if a role exists
     */
    public function roleExists(string $name, string|int|null $teamId = null): bool
    {
        return Role::where('name', $name)
            ->where('team_id', $teamId)
            ->exists();
    }

    /**
     * Get a role by name
     */
    public function getRoleByName(string $name, string|int|null $teamId = null): mixed
    {
        $query = Role::where('name', $name);

        if ($teamId !== null) {
            $query->where('team_id', $teamId);
        }

        return $query->first();
    }

    /**
     * Clear the permission cache
     */
    public function clearPermissionCache(): void
    {
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Give multiple permissions to a role
     */
    public function givePermissionsToRole(mixed $role, array $permissions): void
    {
        $role->givePermissionTo($permissions);
    }
}
