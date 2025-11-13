<?php

declare(strict_types=1);

namespace Nexus\Erp\AuditLogging\Events;

use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Activity Logged Event
 *
 * Dispatched when an activity is successfully logged.
 * Allows other modules to react to logged activities for real-time monitoring,
 * notifications, or analytics.
 */
class ActivityLoggedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  int  $logId  Activity log ID
     * @param  string|null  $tenantId  Tenant ID
     * @param  string  $event  Event type (created, updated, deleted)
     * @param  string  $subjectType  Subject model class
     * @param  int  $subjectId  Subject model ID
     * @param  string|null  $causerType  Causer model class
     * @param  int|null  $causerId  Causer model ID
     * @param  Carbon  $loggedAt  Timestamp when logged
     */
    public function __construct(
        public int $logId,
        public ?string $tenantId,
        public string $event,
        public string $subjectType,
        public int $subjectId,
        public ?string $causerType,
        public ?int $causerId,
        public Carbon $loggedAt
    ) {}

    /**
     * Check if this is a high-value entity event
     *
     * @return bool True if high-value entity
     */
    public function isHighValueEntity(): bool
    {
        $highValueEntities = config('audit-logging.high_value_entities', []);

        return in_array($this->subjectType, $highValueEntities, true);
    }

    /**
     * Check if causer is system
     *
     * @return bool True if system-generated event
     */
    public function isSystemGenerated(): bool
    {
        return $this->causerType === null && $this->causerId === null;
    }
}
