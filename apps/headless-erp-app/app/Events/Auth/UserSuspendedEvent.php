<?php

declare(strict_types=1);

namespace App\Events\Auth;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * User Suspended Event
 *
 * Dispatched when a user account is suspended.
 */
class UserSuspendedEvent
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance
     *
     * @param  User  $user  The suspended user
     * @param  string  $reason  The reason for suspension
     */
    public function __construct(
        public readonly User $user,
        public readonly string $reason
    ) {}
}
