<?php

declare(strict_types=1);

namespace Nexus\Erp\AuditLogging\Listeners;

use Nexus\Erp\AuditLogging\Events\ActivityLoggedEvent;
use Illuminate\Support\Facades\Log;

/**
 * Notify High Value Activity Listener
 *
 * Listens to ActivityLoggedEvent and sends notifications for high-value entity operations.
 * High-value entities are configured in audit-logging.high_value_entities.
 */
class NotifyHighValueActivityListener
{
    /**
     * Handle the event.
     *
     * @param  ActivityLoggedEvent  $event  The activity logged event
     */
    public function handle(ActivityLoggedEvent $event): void
    {
        // Check if this is a high-value entity
        if (! $event->isHighValueEntity()) {
            return;
        }

        // Log the high-value activity
        Log::info('High-value entity activity detected', [
            'log_id' => $event->logId,
            'tenant_id' => $event->tenantId,
            'event' => $event->event,
            'subject_type' => $event->subjectType,
            'subject_id' => $event->subjectId,
            'causer_type' => $event->causerType,
            'causer_id' => $event->causerId,
            'logged_at' => $event->loggedAt->toDateTimeString(),
        ]);

        // TODO: Integration with notification module (SUB22)
        // When SUB22 is implemented, send notification to admins:
        //
        // Notification::send(
        //     User::whereHas('roles', fn($q) => $q->whereIn('name', ['admin', 'super-admin']))->get(),
        //     new HighValueActivityNotification($event)
        // );
        //
        // For now, we just log it for awareness

        // Optional: Send to external monitoring system
        if (config('audit-logging.notify_high_value_events', false)) {
            // Integration point for external systems (Slack, PagerDuty, etc.)
            $this->notifyExternalSystem($event);
        }
    }

    /**
     * Notify external monitoring system
     *
     * Integration point for external notification systems.
     *
     * @param  ActivityLoggedEvent  $event  The activity logged event
     */
    protected function notifyExternalSystem(ActivityLoggedEvent $event): void
    {
        // TODO: Implement external system notification
        // Examples:
        // - Send to Slack webhook
        // - Send to PagerDuty
        // - Send to Microsoft Teams
        // - Send to custom webhook

        // For now, just log that we would notify
        Log::debug('Would notify external system about high-value activity', [
            'log_id' => $event->logId,
            'subject_type' => class_basename($event->subjectType),
            'event' => $event->event,
        ]);
    }
}
