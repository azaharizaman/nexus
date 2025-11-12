<?php

declare(strict_types=1);

namespace App\Actions\Permission;

use App\Events\Permission\RoleRevokedEvent;
use App\Models\User;
use App\Support\Contracts\ActivityLoggerContract;
use App\Support\Contracts\PermissionServiceContract;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Revoke Role From User Action
 *
 * Removes a role from a user and clears permission cache.
 */
class RevokeRoleFromUserAction
{
    use AsAction;

    /**
     * Create a new action instance
     */
    public function __construct(
        private readonly PermissionServiceContract $permissionService,
        private readonly ActivityLoggerContract $activityLogger
    ) {}

    /**
     * Execute the action
     *
     * @param  User  $user  The user to revoke the role from
     * @param  string|object  $role  The role name or role object
     */
    public function handle(User $user, string|object $role): void
    {
        // Verify user has the role
        $roleName = is_string($role) ? $role : $role->name;

        if (! $this->permissionService->hasRole($user, $roleName)) {
            return; // Role not assigned, nothing to do
        }

        // Revoke the role
        $this->permissionService->removeRole($user, $role);

        // Clear user permission cache via contract
        $this->permissionService->clearPermissionCache();

        // Log activity
        if (auth()->check()) {
            $this->activityLogger->log(
                "Role revoked: {$roleName}",
                $user,
                auth()->user(),
                ['role' => $roleName, 'user_id' => $user->id]
            );
        }

        // Dispatch event
        event(new RoleRevokedEvent($user, $role));
    }
}
