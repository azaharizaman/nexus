<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\Core\Events;

use Azaharizaman\Erp\Core\Models\Tenant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Tenant Impersonation Ended Event
 *
 * Dispatched when an admin/support user ends tenant impersonation.
 */
class TenantImpersonationEndedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance
     *
     * @param  Tenant  $tenant  The tenant that was being impersonated
     * @param  int  $userId  The user ending the impersonation
     * @param  int|null  $duration  Duration of impersonation in seconds
     */
    public function __construct(
        public readonly Tenant $tenant,
        public readonly int $userId,
        public readonly ?int $duration = null
    ) {}
}
