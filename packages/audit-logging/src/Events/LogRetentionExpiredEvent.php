<?php

declare(strict_types=1);

namespace Nexus\Erp\AuditLogging\Events;

use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Log Retention Expired Event
 *
 * Dispatched when audit logs are purged due to retention policy expiration.
 * Allows modules to react to log purging (e.g., send notifications to admins).
 */
class LogRetentionExpiredEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  string|null  $tenantId  Tenant ID (null for system-wide purge)
     * @param  int  $purgedCount  Number of logs purged
     * @param  Carbon  $cutoffDate  Cutoff date for purging
     */
    public function __construct(
        public ?string $tenantId,
        public int $purgedCount,
        public Carbon $cutoffDate
    ) {}
}
