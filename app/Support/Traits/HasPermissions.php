<?php

declare(strict_types=1);

namespace App\Support\Traits;

use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;

/**
 * Trait HasPermissions
 *
 * Wrapper trait for role and permission functionality that decouples business logic
 * from the underlying Spatie Permission package. This trait still uses Spatie
 * internally but provides a consistent interface that can be replaced if needed.
 *
 * For direct permission operations in services, inject PermissionServiceContract instead.
 *
 * Usage:
 * ```
 * class User extends Authenticatable
 * {
 *     use HasPermissions;
 *
 *     public function getPermissionTeamId(): int|string|null
 *     {
 *         return $this->tenant_id;
 *     }
 * }
 * ```
 */
trait HasPermissions
{
    use HasRoles;

    /**
     * Get all roles assigned to the user
     */
    public function getUserRoles(): Collection
    {
        return $this->roles;
    }

    /**
     * Get all permissions for the user (direct and via roles)
     */
    public function getUserPermissions(): Collection
    {
        return $this->getAllPermissions();
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(string|array $roles): bool
    {
        return $this->hasRole($roles);
    }

    /**
     * Check if user has all of the given roles
     */
    public function hasAllRoles(string|array $roles): bool
    {
        if (is_string($roles)) {
            return $this->hasRole($roles);
        }

        foreach ($roles as $role) {
            if (! $this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(string|array $permissions): bool
    {
        if (is_string($permissions)) {
            return $this->hasPermissionTo($permissions);
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermissionTo($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions(string|array $permissions): bool
    {
        if (is_string($permissions)) {
            return $this->hasPermissionTo($permissions);
        }

        foreach ($permissions as $permission) {
            if (! $this->hasPermissionTo($permission)) {
                return false;
            }
        }

        return true;
    }
}
