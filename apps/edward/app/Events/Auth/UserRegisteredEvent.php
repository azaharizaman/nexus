<?php

declare(strict_types=1);

namespace Nexus\Erp\Events\Auth;

use Nexus\Erp\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * User Registered Event
 *
 * Dispatched when a new user account is created.
 */
class UserRegisteredEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance
     *
     * @param  User  $user  The newly registered user
     */
    public function __construct(
        public readonly User $user
    ) {
    }
}
