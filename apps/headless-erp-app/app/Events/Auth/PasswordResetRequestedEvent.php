<?php

declare(strict_types=1);

namespace App\Events\Auth;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Password Reset Requested Event
 *
 * Dispatched when a user requests a password reset.
 */
class PasswordResetRequestedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance
     *
     * @param  User  $user  The user requesting password reset
     * @param  string  $token  The password reset token
     */
    public function __construct(
        public readonly User $user,
        public readonly string $token
    ) {}
}
