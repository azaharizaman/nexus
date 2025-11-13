<?php

declare(strict_types=1);

namespace Nexus\Erp\Core\Policies;

use App\Models\User;
use Nexus\Erp\Core\Models\Tenant;

/**
 * Tenant Policy
 *
 * Authorization policy for tenant management operations.
 * Enforces permission-based access control for tenant operations.
 * All tenant management operations require super admin privileges.
 */
class TenantPolicy
{
    /**
     * Determine whether the user can view any tenants.
     *
     * Checks for 'view tenants' permission.
     *
     * @param  User  $user  The authenticated user
     * @return bool True if user has permission to view tenants
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the tenant.
     *
     * Checks for 'view tenants' permission.
     *
     * @param  User  $user  The authenticated user
     * @param  Tenant  $tenant  The tenant to view
     * @return bool True if user has permission to view the tenant
     */
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create tenants.
     *
     * Checks for 'create tenants' permission (super admin only).
     *
     * @param  User  $user  The authenticated user
     * @return bool True if user has permission to create tenants
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the tenant.
     *
     * Checks for 'update tenants' permission (super admin only).
     *
     * @param  User  $user  The authenticated user
     * @param  Tenant  $tenant  The tenant to update
     * @return bool True if user has permission to update the tenant
     */
    public function update(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the tenant.
     *
     * Checks for 'delete tenants' permission (super admin only).
     *
     * @param  User  $user  The authenticated user
     * @param  Tenant  $tenant  The tenant to delete
     * @return bool True if user has permission to delete the tenant
     */
    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the tenant.
     *
     * Checks for 'restore tenants' permission (super admin only).
     *
     * @param  User  $user  The authenticated user
     * @param  Tenant  $tenant  The tenant to restore
     * @return bool True if user has permission to restore the tenant
     */
    public function restore(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the tenant.
     *
     * Checks for 'force delete tenants' permission (super admin only).
     *
     * @param  User  $user  The authenticated user
     * @param  Tenant  $tenant  The tenant to force delete
     * @return bool True if user has permission to permanently delete the tenant
     */
    public function forceDelete(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }
}
