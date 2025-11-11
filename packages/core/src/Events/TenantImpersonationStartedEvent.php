<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\Core\Events;

use Azaharizaman\Erp\Core\Models\Tenant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Tenant Impersonation Started Event
 *
 * Dispatched when an admin/support user starts impersonating a tenant.
 */
class TenantImpersonationStartedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance
     *
     * @param  Tenant  $tenant  The tenant being impersonated
     * @param  int  $userId  The user starting the impersonation
     * @param  string  $reason  The reason for impersonation
     */
    public function __construct(
        public readonly Tenant $tenant,
        public readonly int $userId,
        public readonly string $reason
    ) {}
}
