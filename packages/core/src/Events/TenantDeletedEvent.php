<?php

declare(strict_types=1);

namespace Nexus\Erp\Core\Events;

use Nexus\Erp\Core\Models\Tenant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Tenant Deleted Event
 *
 * Dispatched when a tenant is soft-deleted.
 */
class TenantDeletedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance
     *
     * @param  Tenant  $tenant  The deleted tenant
     */
    public function __construct(
        public readonly Tenant $tenant
    ) {}
}
