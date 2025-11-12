<?php

declare(strict_types=1);

namespace App\Listeners\Auth;

use App\Events\Auth\UserLoggedInEvent;
use Illuminate\Events\Attribute\Listen;
use Illuminate\Support\Facades\Log;

/**
 * Log Authentication Success Listener
 *
 * Logs successful authentication attempts for security auditing.
 */
class LogAuthenticationSuccessListener
{
    /**
     * Handle the event
     */
    #[Listen(UserLoggedInEvent::class)]
    public function handle(UserLoggedInEvent $event): void
    {
        // Log to info channel
        Log::info('User logged in successfully', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'tenant_id' => $event->user->tenant_id,
            'device_name' => $event->deviceName,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);

        // If activity logging is available, log there too
        if (function_exists('activity')) {
            activity()
                ->causedBy($event->user)
                ->withProperties([
                    'device_name' => $event->deviceName,
                    'ip_address' => request()->ip(),
                ])
                ->log('User logged in');
        }
    }
}
