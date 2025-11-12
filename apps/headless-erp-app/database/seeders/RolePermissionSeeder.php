<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\Contracts\PermissionServiceContract;
use Illuminate\Database\Seeder;

/**
 * Role and Permission Seeder
 *
 * Seeds default roles and permissions for the RBAC system.
 * Creates tenant-scoped roles and global permissions.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissionService = app(PermissionServiceContract::class);

        // Create permissions (name only - Spatie Permission doesn't have description/category fields)
        $permissionNames = [
            // User management permissions
            'view-users',
            'create-users',
            'update-users',
            'delete-users',
            'suspend-users',

            // Role management permissions
            'view-roles',
            'manage-roles',
            'assign-roles',

            // Permission management
            'view-permissions',
            'manage-permissions',

            // Tenant management permissions
            'view-tenants',
            'create-tenants',
            'update-tenants',
            'delete-tenants',
            'suspend-tenants',
            'activate-tenants',
            'archive-tenants',
            'impersonate-tenants',
        ];

        // Create all permissions
        $createdPermissions = [];
        foreach ($permissionNames as $name) {
            $createdPermissions[] = $permissionService->createPermission($name);
        }

        // Create global super-admin role (no tenant_id)
        $superAdminRole = $permissionService->createRole('super-admin', null);

        // Grant all permissions to super-admin role
        foreach ($createdPermissions as $permission) {
            $permissionService->givePermissionToRole($superAdminRole, $permission);
        }

        // Note: Tenant-specific roles (tenant-admin, manager, user) should be created
        // when a tenant is created, using the tenant_id for proper scoping.
        // This is handled by the InitializeTenantDataListener.
    }
}
