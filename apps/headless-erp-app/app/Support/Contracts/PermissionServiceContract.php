<?php

declare(strict_types=1);

namespace App\Support\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Permission Service Contract
 *
 * Abstraction for role-based access control (RBAC) functionality.
 * Decouples business logic from the underlying permission package.
 *
 * Implementation: SpatiePermissionService (wraps spatie/laravel-permission)
 */
interface PermissionServiceContract
{
    /**
     * Create a new role
     *
     * @param  string  $name  The role name
     * @param  string|int|null  $teamId  The team/tenant ID for scoping (optional)
     * @param  string  $guardName  The guard name (default: 'web')
     * @return mixed The created role
     */
    public function createRole(string $name, string|int|null $teamId = null, string $guardName = 'web'): mixed;

    /**
     * Create a new permission
     *
     * @param  string  $name  The permission name
     * @param  string  $guardName  The guard name (default: 'web')
     * @return mixed The created permission
     */
    public function createPermission(string $name, string $guardName = 'web'): mixed;

    /**
     * Assign a role to a user
     *
     * @param  Model  $user  The user to assign the role to
     * @param  string|mixed  $role  The role name or role object
     */
    public function assignRole(Model $user, string|object $role): void;

    /**
     * Assign multiple roles to a user
     *
     * @param  Model  $user  The user to assign roles to
     * @param  array  $roles  Array of role names or role objects
     */
    public function assignRoles(Model $user, array $roles): void;

    /**
     * Remove a role from a user
     *
     * @param  Model  $user  The user to remove the role from
     * @param  string|mixed  $role  The role name or role object
     */
    public function removeRole(Model $user, string|object $role): void;

    /**
     * Check if a user has a specific role
     *
     * @param  Model  $user  The user to check
     * @param  string  $role  The role name
     */
    public function hasRole(Model $user, string $role): bool;

    /**
     * Give a permission directly to a user
     *
     * @param  Model  $user  The user to give permission to
     * @param  string|mixed  $permission  The permission name or permission object
     */
    public function givePermissionTo(Model $user, string|object $permission): void;

    /**
     * Revoke a permission from a user
     *
     * @param  Model  $user  The user to revoke permission from
     * @param  string|mixed  $permission  The permission name or permission object
     */
    public function revokePermissionTo(Model $user, string|object $permission): void;

    /**
     * Check if a user has a specific permission
     *
     * @param  Model  $user  The user to check
     * @param  string  $permission  The permission name
     */
    public function hasPermissionTo(Model $user, string $permission): bool;

    /**
     * Give a permission to a role
     *
     * @param  mixed  $role  The role object
     * @param  string|mixed  $permission  The permission name or permission object
     */
    public function givePermissionToRole(mixed $role, string|object $permission): void;

    /**
     * Get all roles for a user
     *
     * @param  Model  $user  The user to get roles for
     */
    public function getUserRoles(Model $user): Collection;

    /**
     * Get all permissions for a user (direct and via roles)
     *
     * @param  Model  $user  The user to get permissions for
     */
    public function getUserPermissions(Model $user): Collection;

    /**
     * Set the current team/tenant context for permission checks
     *
     * @param  string|int  $teamId  The team/tenant ID
     */
    public function setPermissionsTeamId(string|int $teamId): void;

    /**
     * Get the current team/tenant context
     */
    public function getPermissionsTeamId(): string|int|null;

    /**
     * Check if a role exists
     *
     * @param  string  $name  The role name
     * @param  string|int|null  $teamId  The team/tenant ID for scoping (optional)
     */
    public function roleExists(string $name, string|int|null $teamId = null): bool;

    /**
     * Get a role by name
     *
     * @param  string  $name  The role name
     * @param  string|int|null  $teamId  The team/tenant ID for scoping (optional)
     * @return mixed The role object or null if not found
     */
    public function getRoleByName(string $name, string|int|null $teamId = null): mixed;

    /**
     * Clear the permission cache
     */
    public function clearPermissionCache(): void;

    /**
     * Give multiple permissions to a role
     *
     * @param  mixed  $role  The role object
     * @param  array  $permissions  Array of permission names or permission objects
     */
    public function givePermissionsToRole(mixed $role, array $permissions): void;
}
