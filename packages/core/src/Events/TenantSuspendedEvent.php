<?php

declare(strict_types=1);

namespace Nexus\Erp\Core\Events;

use Nexus\Erp\Core\Models\Tenant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Tenant Suspended Event
 *
 * Dispatched when a tenant is suspended.
 */
class TenantSuspendedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance
     *
     * @param  Tenant  $tenant  The suspended tenant
     * @param  string  $reason  The reason for suspension
     */
    public function __construct(
        public readonly Tenant $tenant,
        public readonly string $reason
    ) {}
}
