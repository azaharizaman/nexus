<?php

declare(strict_types=1);

namespace Nexus\Erp\Policies;

use Nexus\Erp\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * User Policy
 *
 * Authorization policy for User model operations.
 * Enforces tenant isolation and role-based permissions.
 */
class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any users
     *
     * @param  User  $user  The authenticated user
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-users');
    }

    /**
     * Determine if the user can view a specific user
     *
     * @param  User  $user  The authenticated user
     * @param  User  $model  The user being viewed
     */
    public function view(User $user, User $model): bool
    {
        // User can view users in their own tenant
        // Note: Super-admin bypass handled by Gate::before() in AuthServiceProvider
        return $user->hasPermissionTo('view-users') && $user->tenant_id === $model->tenant_id;
    }

    /**
     * Determine if the user can create users
     *
     * @param  User  $user  The authenticated user
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-users');
    }

    /**
     * Determine if the user can update a specific user
     *
     * @param  User  $user  The authenticated user
     * @param  User  $model  The user being updated
     */
    public function update(User $user, User $model): bool
    {
        // User can update users in their own tenant
        // Note: Super-admin bypass handled by Gate::before() in AuthServiceProvider
        return $user->hasPermissionTo('update-users') && $user->tenant_id === $model->tenant_id;
    }

    /**
     * Determine if the user can delete a specific user
     *
     * @param  User  $user  The authenticated user
     * @param  User  $model  The user being deleted
     */
    public function delete(User $user, User $model): bool
    {
        // User can delete users in their own tenant (but not themselves)
        // Note: Super-admin bypass handled by Gate::before() in AuthServiceProvider
        return $user->hasPermissionTo('delete-users')
            && $user->tenant_id === $model->tenant_id
            && $user->id !== $model->id;
    }

    /**
     * Determine if the user can restore a deleted user
     *
     * @param  User  $user  The authenticated user
     * @param  User  $model  The user being restored
     */
    public function restore(User $user, User $model): bool
    {
        // Only super admin can restore users
        return $user->hasRole('super-admin');
    }

    /**
     * Determine if the user can permanently delete a user
     *
     * @param  User  $user  The authenticated user
     * @param  User  $model  The user being permanently deleted
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Only super admin can permanently delete users
        return $user->hasRole('super-admin');
    }

    /**
     * Determine if the user can suspend another user
     *
     * @param  User  $user  The authenticated user
     * @param  User  $model  The user being suspended
     */
    public function suspend(User $user, User $model): bool
    {
        // User can suspend users in their own tenant (but not themselves)
        // Note: Super-admin bypass handled by Gate::before() in AuthServiceProvider
        return $user->hasPermissionTo('suspend-users')
            && $user->tenant_id === $model->tenant_id
            && $user->id !== $model->id;
    }
}
