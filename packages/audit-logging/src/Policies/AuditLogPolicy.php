<?php

declare(strict_types=1);

namespace Nexus\Erp\AuditLogging\Policies;

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

/**
 * Audit Log Policy
 *
 * Authorization policies for audit log access and operations.
 */
class AuditLogPolicy
{
    /**
     * Determine whether the user can view any audit logs.
     *
     * @param  User  $user  The authenticated user
     * @return bool True if user can view audit logs
     */
    public function viewAny(User $user): bool
    {
        // Check if user has 'view-audit-logs' permission
        if (method_exists($user, 'can')) {
            return $user->can('view-audit-logs');
        }

        // Fallback: check if user has roles
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole(['super-admin', 'admin', 'auditor']);
        }

        return false;
    }

    /**
     * Determine whether the user can view the audit log.
     *
     * @param  User  $user  The authenticated user
     * @param  Activity  $activity  The activity log
     * @return bool True if user can view this specific log
     */
    public function view(User $user, Activity $activity): bool
    {
        // Super admin can view all logs
        if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
            return true;
        }

        // Check tenant isolation - users can only view logs from their tenant
        if (isset($user->tenant_id) && isset($activity->tenant_id)) {
            return $user->tenant_id === $activity->tenant_id;
        }

        // If no tenant_id on either, allow if user has view-audit-logs permission
        if (method_exists($user, 'can')) {
            return $user->can('view-audit-logs');
        }

        return false;
    }

    /**
     * Determine whether the user can export audit logs.
     *
     * @param  User  $user  The authenticated user
     * @return bool True if user can export logs
     */
    public function export(User $user): bool
    {
        // Check if user has 'export-audit-logs' permission
        if (method_exists($user, 'can')) {
            return $user->can('export-audit-logs');
        }

        // Fallback: check if user has admin role
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole(['super-admin', 'admin']);
        }

        return false;
    }

    /**
     * Determine whether the user can purge audit logs.
     *
     * @param  User  $user  The authenticated user
     * @return bool True if user can purge logs
     */
    public function purge(User $user): bool
    {
        // Only super admins can purge logs
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('super-admin');
        }

        return false;
    }

    /**
     * Determine whether the user can view audit log statistics.
     *
     * @param  User  $user  The authenticated user
     * @return bool True if user can view statistics
     */
    public function viewStatistics(User $user): bool
    {
        // Same as viewAny
        return $this->viewAny($user);
    }
}
