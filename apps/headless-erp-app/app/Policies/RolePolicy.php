<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Models\Role;

/**
 * Role Policy
 *
 * Authorization policy for Role model operations.
 * Enforces tenant isolation and prevents deletion of critical roles.
 */
class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any roles
     *
     * @param  User  $user  The authenticated user
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-roles');
    }

    /**
     * Determine if the user can view a specific role
     *
     * @param  User  $user  The authenticated user
     * @param  Role  $role  The role being viewed
     */
    public function view(User $user, Role $role): bool
    {
        // User can view roles in their own tenant or global roles (team_id is null)
        // Note: Super-admin bypass handled by Gate::before() in AuthServiceProvider
        return $user->hasPermissionTo('view-roles')
            && ($role->team_id === null || $user->tenant_id === $role->team_id);
    }

    /**
     * Determine if the user can create roles
     *
     * @param  User  $user  The authenticated user
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage-roles');
    }

    /**
     * Determine if the user can update a specific role
     *
     * @param  User  $user  The authenticated user
     * @param  Role  $role  The role being updated
     */
    public function update(User $user, Role $role): bool
    {
        // Cannot update super-admin role (unless user is super-admin via Gate::before())
        if ($role->name === 'super-admin') {
            return false;
        }

        // User can update roles in their own tenant or global roles
        // Note: Super-admin bypass handled by Gate::before() in AuthServiceProvider
        return $user->hasPermissionTo('manage-roles')
            && ($role->team_id === null || $user->tenant_id === $role->team_id);
    }

    /**
     * Determine if the user can delete a specific role
     *
     * @param  User  $user  The authenticated user
     * @param  Role  $role  The role being deleted
     */
    public function delete(User $user, Role $role): bool
    {
        // Cannot delete super-admin role
        if ($role->name === 'super-admin') {
            return false;
        }

        // User can delete roles in their own tenant
        // Note: Super-admin bypass handled by Gate::before() in AuthServiceProvider
        return $user->hasPermissionTo('manage-roles')
            && $user->tenant_id === $role->team_id;
    }

    /**
     * Determine if the user can assign a role to users
     *
     * @param  User  $user  The authenticated user
     * @param  Role  $role  The role being assigned
     */
    public function assign(User $user, Role $role): bool
    {
        // Cannot assign super-admin role (unless user is super-admin via Gate::before())
        if ($role->name === 'super-admin') {
            return false;
        }

        // User can assign roles in their own tenant or global roles
        // Note: Super-admin bypass handled by Gate::before() in AuthServiceProvider
        return $user->hasPermissionTo('assign-roles')
            && ($role->team_id === null || $user->tenant_id === $role->team_id);
    }
}
