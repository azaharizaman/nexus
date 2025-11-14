<?php

declare(strict_types=1);

namespace Nexus\AuditLog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

/**
 * Internal Audit Log Model
 *
 * Internal representation of audit log entries for the atomic package.
 * This model abstracts away external dependencies (Spatie ActivityLog)
 * to enable independent testing and reduce coupling.
 *
 * This model maps to the same database table as Spatie's Activity model
 * but provides our own domain-specific interface.
 */
class AuditLog extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'activity_log';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'event',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'batch_uuid',
        'tenant_id',
        'ip_address',
        'user_agent',
        'audit_level',
        'retention_days',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'audit_level' => 'integer',
        'retention_days' => 'integer',
    ];

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * Get the subject that the audit log belongs to.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the causer that performed the action.
     */
    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include logs for a specific tenant.
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to only include logs within a date range.
     */
    public function scopeWithinDateRange($query, Carbon $from, Carbon $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope a query to only include logs for a specific subject.
     */
    public function scopeForSubject($query, string $subjectType, int $subjectId)
    {
        return $query->where('subject_type', $subjectType)
                    ->where('subject_id', $subjectId);
    }

    /**
     * Scope a query to only include logs by a specific causer.
     */
    public function scopeByCauser($query, string $causerType, int $causerId)
    {
        return $query->where('causer_type', $causerType)
                    ->where('causer_id', $causerId);
    }

    /**
     * Scope a query to search logs by text.
     */
    public function scopeSearchText($query, string $search)
    {
        return $query->where(function ($query) use ($search) {
            $query->where('description', 'LIKE', "%{$search}%")
                  ->orWhere('properties', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Scope a query to filter by event type.
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope a query to filter by log name.
     */
    public function scopeForLogName($query, string $logName)
    {
        return $query->where('log_name', $logName);
    }

    /**
     * Get the audit level as a human-readable string.
     */
    public function getAuditLevelNameAttribute(): string
    {
        return match ($this->audit_level) {
            1 => 'Low',
            2 => 'Medium', 
            3 => 'High',
            4 => 'Critical',
            default => 'Unknown',
        };
    }

    /**
     * Get the retention period for this log entry.
     */
    public function getRetentionPeriodAttribute(): int
    {
        return $this->retention_days ?? config('audit-logging.retention_days', 90);
    }

    /**
     * Check if this log entry has expired based on retention policy.
     */
    public function hasExpired(): bool
    {
        $expirationDate = $this->created_at->addDays($this->retention_period);
        return now()->isAfter($expirationDate);
    }

    /**
     * Get a formatted description of the audit log.
     */
    public function getFormattedDescriptionAttribute(): string
    {
        $description = $this->description;
        
        if ($this->subject) {
            $subjectName = class_basename($this->subject_type);
            $description = str_replace(
                ['{subject}', '{subject_id}'],
                [strtolower($subjectName), $this->subject_id],
                $description
            );
        }

        return $description;
    }
}