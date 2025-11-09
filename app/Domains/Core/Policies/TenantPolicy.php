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
     * @return bool
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
     * @return bool
     */
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create tenants.
     *
     * @param  User  $user  The authenticated user
     * @return bool
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
     * @return bool
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
     * @return bool
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
     * @return bool
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
     * @return bool
     */
    public function forceDelete(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }
}
