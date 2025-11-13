<?php

declare(strict_types=1);

namespace Nexus\Erp\Events\Permission;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Role Created Event
 *
 * Dispatched when a new role is created in the system.
 */
class RoleCreatedEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance
     *
     * @param  mixed  $role  The created role
     * @param  array  $permissions  The permissions assigned to the role
     * @param  string|int|null  $tenantId  The tenant ID (null for global roles)
     */
    public function __construct(
        public readonly mixed $role,
        public readonly array $permissions,
        public readonly string|int|null $tenantId
    ) {
    }
}
