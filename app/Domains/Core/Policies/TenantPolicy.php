<?php

declare(strict_types=1);

namespace App\Domains\Core\Policies;

use App\Domains\Core\Models\Tenant;
use App\Models\User;

/**
 * Tenant Policy
 *
 * Authorization policy for tenant management operations.
 * Only admin users can manage tenants.
 */
class TenantPolicy
{
    /**
     * Determine whether the user can view any tenants.
     *
     * @param  User  $user  The authenticated user
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the tenant.
     *
     * @param  User  $user  The authenticated user
     * @param  Tenant  $tenant  The tenant to view
     */
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create tenants.
     *
     * @param  User  $user  The authenticated user
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the tenant.
     *
     * @param  User  $user  The authenticated user
     * @param  Tenant  $tenant  The tenant to update
     */
    public function update(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the tenant.
     *
     * @param  User  $user  The authenticated user
     * @param  Tenant  $tenant  The tenant to delete
     */
    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the tenant.
     *
     * @param  User  $user  The authenticated user
     * @param  Tenant  $tenant  The tenant to restore
     */
    public function restore(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the tenant.
     *
     * @param  User  $user  The authenticated user
     * @param  Tenant  $tenant  The tenant to force delete
     */
    public function forceDelete(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }
}
