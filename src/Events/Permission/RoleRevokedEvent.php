<?php

declare(strict_types=1);

namespace Nexus\Erp\Events\Permission;

use Nexus\Erp\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Role Revoked Event
 *
 * Dispatched when a role is revoked from a user.
 */
class RoleRevokedEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance
     *
     * @param  User  $user  The user losing the role
     * @param  string|object  $role  The role being revoked
     */
    public function __construct(
        public readonly User $user,
        public readonly string|object $role
    ) {
    }
}
