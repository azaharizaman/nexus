<?php

declare(strict_types=1);

namespace Nexus\Hrm\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisciplinaryCase extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'hrm_disciplinary_cases';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'case_type',
        'severity',
        'description',
        'incident_date',
        'reported_date',
        'status',
        'handler_id',
        'resolution',
        'resolution_date',
        'follow_up_required',
        'follow_up_date',
        'documents',
        'witnesses',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'reported_date' => 'date',
        'resolution_date' => 'date',
        'follow_up_date' => 'date',
        'follow_up_required' => 'boolean',
        'documents' => 'array',
        'witnesses' => 'array',
    ];

    /**
     * Get the employee this case belongs to
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the case handler (HR manager/supervisor)
     */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'handler_id');
    }

    /**
     * Scope for tenant
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope for employee
     */
    public function scopeForEmployee($query, string $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope for status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for severity
     */
    public function scopeWithSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Check if case is open
     */
    public function isOpen(): bool
    {
        return in_array($this->status, ['investigating', 'pending_resolution']);
    }

    /**
     * Check if case is closed
     */
    public function isClosed(): bool
    {
        return in_array($this->status, ['resolved', 'dismissed']);
    }

    /**
     * Check if follow-up is required
     */
    public function requiresFollowUp(): bool
    {
        return $this->follow_up_required && $this->follow_up_date && $this->follow_up_date->isFuture();
    }
}