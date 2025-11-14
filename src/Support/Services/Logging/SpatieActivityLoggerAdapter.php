<?php

declare(strict_types=1);

namespace Nexus\Erp\Support\Services\Logging;

use Nexus\AuditLog\Contracts\AuditLogRepositoryContract;
use Nexus\AuditLog\Models\AuditLog as InternalAuditLog;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;
use Spatie\Activitylog\Models\Activity;

/**
 * Spatie Activity Logger Adapter
 *
 * Bridges the internal atomic AuditLog package with Spatie's ActivityLog
 * for backward compatibility and external integrations.
 *
 * This adapter lives in the orchestration layer (Nexus\Erp) and handles
 * the conversion between our internal models and external interfaces.
 */
class SpatieActivityLoggerAdapter
{
    public function __construct(
        private AuditLogRepositoryContract $auditLogRepository
    ) {}

    /**
     * Create an activity log using internal repository
     * and return a Spatie-compatible Activity model
     */
    public function logActivity(array $data): Activity
    {
        // Use the internal audit log repository
        $internalLog = $this->auditLogRepository->create($data);
        
        // Convert to Spatie Activity model for external compatibility
        return $this->convertToSpatieActivity($internalLog);
    }

    /**
     * Find an activity by ID and return as Spatie Activity model
     */
    public function findActivity(int $id): ?Activity
    {
        $internalLog = $this->auditLogRepository->find($id);
        
        if (!$internalLog) {
            return null;
        }
        
        return $this->convertToSpatieActivity($internalLog);
    }

    /**
     * Convert internal AuditLog model to Spatie Activity model
     *
     * This maintains backward compatibility for external integrations
     * that expect Spatie's Activity model structure.
     */
    public function convertToSpatieActivity(InternalAuditLog $internalLog): Activity
    {
        // Create a new Activity instance with the same data
        $activity = new Activity();
        
        // Copy all attributes
        $activity->id = $internalLog->id;
        $activity->log_name = $internalLog->log_name;
        $activity->description = $internalLog->description;
        $activity->subject_type = $internalLog->subject_type;
        $activity->subject_id = $internalLog->subject_id;
        $activity->causer_type = $internalLog->causer_type;
        $activity->causer_id = $internalLog->causer_id;
        $activity->event = $internalLog->event;
        $activity->properties = $internalLog->properties;
        $activity->batch_uuid = $internalLog->batch_uuid;
        $activity->created_at = $internalLog->created_at;
        $activity->updated_at = $internalLog->updated_at;
        
        // Extended attributes from our enhanced schema
        if (isset($internalLog->tenant_id)) {
            $activity->setAttribute('tenant_id', $internalLog->tenant_id);
        }
        if (isset($internalLog->ip_address)) {
            $activity->setAttribute('ip_address', $internalLog->ip_address);
        }
        if (isset($internalLog->user_agent)) {
            $activity->setAttribute('user_agent', $internalLog->user_agent);
        }
        if (isset($internalLog->audit_level)) {
            $activity->setAttribute('audit_level', $internalLog->audit_level);
        }
        if (isset($internalLog->retention_days)) {
            $activity->setAttribute('retention_days', $internalLog->retention_days);
        }
        
        // Mark as existing in database
        $activity->exists = true;
        $activity->wasRecentlyCreated = false;
        
        return $activity;
    }

    /**
     * Convert Spatie Activity model to internal AuditLog model
     *
     * Used when external code provides Activity models that need
     * to be processed by our internal audit log system.
     */
    public function convertFromSpatieActivity(Activity $activity): InternalAuditLog
    {
        // Create internal audit log from Spatie activity
        $data = [
            'log_name' => $activity->log_name,
            'description' => $activity->description,
            'subject_type' => $activity->subject_type,
            'subject_id' => $activity->subject_id,
            'causer_type' => $activity->causer_type,
            'causer_id' => $activity->causer_id,
            'event' => $activity->event,
            'properties' => $activity->properties ?? [],
            'batch_uuid' => $activity->batch_uuid,
            
            // Extended attributes
            'tenant_id' => $activity->getAttribute('tenant_id'),
            'ip_address' => $activity->getAttribute('ip_address'),
            'user_agent' => $activity->getAttribute('user_agent'),
            'audit_level' => $activity->getAttribute('audit_level') ?? 1,
            'retention_days' => $activity->getAttribute('retention_days') ?? config('audit-logging.retention_days', 90),
        ];
        
        return $this->auditLogRepository->create($data);
    }
}