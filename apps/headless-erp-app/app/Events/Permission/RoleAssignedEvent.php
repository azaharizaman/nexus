<?php

declare(strict_types=1);

namespace App\Events\Permission;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Role Assigned Event
 *
 * Dispatched when a role is assigned to a user.
 */
class RoleAssignedEvent
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance
     *
     * @param  User  $user  The user receiving the role
     * @param  string|object  $role  The role being assigned
     */
    public function __construct(
        public readonly User $user,
        public readonly string|object $role
    ) {}
}
