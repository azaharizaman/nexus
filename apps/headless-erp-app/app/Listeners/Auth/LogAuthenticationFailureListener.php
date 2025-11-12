<?php

declare(strict_types=1);

namespace App\Listeners\Auth;

use App\Events\Auth\LoginFailedEvent;
use Illuminate\Events\Attribute\Listen;
use Illuminate\Support\Facades\Log;

/**
 * Log Authentication Failure Listener
 *
 * Logs failed authentication attempts for security auditing.
 */
class LogAuthenticationFailureListener
{
    /**
     * Handle the event
     */
    #[Listen(LoginFailedEvent::class)]
    public function handle(LoginFailedEvent $event): void
    {
        // Log to default channel
        Log::warning('Failed login attempt', [
            'email' => $event->email,
            'tenant_id' => $event->tenantId,
            'attempts_remaining' => $event->attemptsRemaining,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);

        // If activity logging is available, log there too
        if (function_exists('activity')) {
            activity()
                ->withProperties([
                    'email' => $event->email,
                    'tenant_id' => $event->tenantId,
                    'attempts_remaining' => $event->attemptsRemaining,
                    'ip_address' => request()->ip(),
                ])
                ->log('Failed login attempt');
        }
    }
}
