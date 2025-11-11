<?php

declare(strict_types=1);

namespace App\Events\Auth;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Password Reset Event
 *
 * Dispatched when a user successfully resets their password.
 */
class PasswordResetEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance
     *
     * @param  User  $user  The user who reset their password
     */
    public function __construct(
        public readonly User $user
    ) {}
}
