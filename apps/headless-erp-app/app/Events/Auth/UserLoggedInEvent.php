<?php

declare(strict_types=1);

namespace App\Events\Auth;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * User Logged In Event
 *
 * Dispatched when a user successfully authenticates and receives an API token.
 */
class UserLoggedInEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance
     *
     * @param  User  $user  The authenticated user
     * @param  string  $token  The generated API token
     * @param  string  $deviceName  Name of the device
     */
    public function __construct(
        public readonly User $user,
        public readonly string $token,
        public readonly string $deviceName
    ) {}
}
