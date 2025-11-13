<?php

declare(strict_types=1);

namespace Nexus\Erp\Events\Auth;

use Nexus\Erp\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * User Logged Out Event
 *
 * Dispatched when a user logs out and their token is revoked.
 */
class UserLoggedOutEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance
     *
     * @param  User  $user  The user who logged out
     * @param  int|null  $tokenId  ID of the revoked token
     */
    public function __construct(
        public readonly User $user,
        public readonly ?int $tokenId
    ) {
    }
}
