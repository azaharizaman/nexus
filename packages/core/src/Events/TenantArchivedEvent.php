<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\Core\Events;

use Azaharizaman\Erp\Core\Models\Tenant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Tenant Archived Event
 *
 * Dispatched when a tenant is successfully archived (soft deleted).
 */
class TenantArchivedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance
     *
     * @param  Tenant  $tenant  The archived tenant
     */
    public function __construct(
        public readonly Tenant $tenant
    ) {}
}
