<?php

declare(strict_types=1);

namespace Nexus\Erp\Events\Auth;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Login Failed Event
 *
 * Dispatched when a login attempt fails due to invalid credentials.
 */
class LoginFailedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance
     *
     * @param  string  $email  Email used in the failed attempt
     * @param  string  $tenantId  Tenant ID
     * @param  int  $attemptsRemaining  Number of attempts remaining before lockout
     */
    public function __construct(
        public readonly string $email,
        public readonly string $tenantId,
        public readonly int $attemptsRemaining
    ) {
    }
}
