<?php

declare(strict_types=1);

namespace App\Actions\Permission;

use App\Events\Permission\RoleCreatedEvent;
use App\Support\Contracts\ActivityLoggerContract;
use App\Support\Contracts\PermissionServiceContract;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Create Role Action
 *
 * Creates a new role with optional permissions and tenant scoping.
 */
class CreateRoleAction
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
     * @param  string  $name  The role name
     * @param  array  $permissions  Array of permission names or objects
     * @param  string|int|null  $tenantId  The tenant ID for scoping (null for global)
     * @return mixed The created role
     *
     * @throws ValidationException
     */
    public function handle(string $name, array $permissions = [], string|int|null $tenantId = null): mixed
    {
        // Validate inputs
        $this->validate($name, $tenantId);

        // Create the role
        $role = $this->permissionService->createRole($name, $tenantId);

        // Assign permissions to the role
        foreach ($permissions as $permission) {
            $this->permissionService->givePermissionToRole($role, $permission);
        }

        // Log activity
        if (auth()->check()) {
            $this->activityLogger->log(
                "Role created: {$name}",
                $role,
                auth()->user(),
                ['permissions' => $permissions, 'tenant_id' => $tenantId]
            );
        }

        // Dispatch event
        event(new RoleCreatedEvent($role, $permissions, $tenantId));

        return $role;
    }

    /**
     * Validate the role creation data
     *
     * @param  string  $name  The role name
     * @param  string|int|null  $tenantId  The tenant ID
     *
     * @throws ValidationException
     */
    protected function validate(string $name, string|int|null $tenantId): void
    {
        // Check if role already exists for this tenant using contract
        if ($this->permissionService->roleExists($name, $tenantId)) {
            throw ValidationException::withMessages([
                'name' => ['A role with this name already exists for this tenant.'],
            ]);
        }
    }
}
