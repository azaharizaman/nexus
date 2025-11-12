<?php

declare(strict_types=1);

namespace App\Actions\Permission;

use App\Events\Permission\RoleAssignedEvent;
use App\Models\User;
use App\Support\Contracts\ActivityLoggerContract;
use App\Support\Contracts\PermissionServiceContract;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Assign Role To User Action
 *
 * Assigns a role to a user with tenant validation and cache clearing.
 */
class AssignRoleToUserAction
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
     * @param  User  $user  The user to assign the role to
     * @param  string|object  $role  The role name or role object
     *
     * @throws ValidationException
     */
    public function handle(User $user, string|object $role): void
    {
        // Validate tenant match
        $this->validateTenantMatch($user, $role);

        // Assign the role
        $this->permissionService->assignRole($user, $role);

        // Clear user permission cache
        $this->clearUserPermissionCache($user);

        // Get role name for logging
        $roleName = is_string($role) ? $role : $role->name;

        // Log activity
        if (auth()->check()) {
            $this->activityLogger->log(
                "Role assigned: {$roleName}",
                $user,
                auth()->user(),
                ['role' => $roleName, 'user_id' => $user->id]
            );
        }

        // Dispatch event
        event(new RoleAssignedEvent($user, $role));
    }

    /**
     * Validate that user and role belong to the same tenant
     *
     * @param  User  $user  The user
     * @param  string|object  $role  The role
     *
     * @throws ValidationException
     */
    protected function validateTenantMatch(User $user, string|object $role): void
    {
        // If role is a string, fetch the role object via contract
        if (is_string($role)) {
            $roleObject = $this->permissionService->getRoleByName($role);
        } else {
            $roleObject = $role;
        }

        // Skip validation for global roles (team_id is null)
        if ($roleObject->team_id === null) {
            return;
        }

        // Verify tenant match
        if ($user->tenant_id !== $roleObject->team_id) {
            throw ValidationException::withMessages([
                'role' => ['Cannot assign a role from a different tenant.'],
            ]);
        }
    }

    /**
     * Clear user permission cache
     *
     * @param  User  $user  The user
     */
    protected function clearUserPermissionCache(User $user): void
    {
        // Use contract method to maintain package decoupling
        $this->permissionService->clearPermissionCache();
    }
}
