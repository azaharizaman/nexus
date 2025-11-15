<?php

declare(strict_types=1);

namespace Nexus\Hrm\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingEnrollment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'hrm_training_enrollments';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'training_id',
        'enrolled_at',
        'scheduled_date',
        'completion_date',
        'status', // enrolled, completed, cancelled, no_show
        'score',
        'feedback',
        'certificate_issued',
        'certificate_number',
        'certificate_expiry',
        'notes',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'scheduled_date' => 'date',
        'completion_date' => 'date',
        'certificate_expiry' => 'date',
        'score' => 'decimal:2',
        'certificate_issued' => 'boolean',
    ];

    /**
     * Get the employee this enrollment belongs to
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the training this enrollment is for
     */
    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
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
     * Scope for training
     */
    public function scopeForTraining($query, string $trainingId)
    {
        return $query->where('training_id', $trainingId);
    }

    /**
     * Scope for status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check if enrollment is active
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['enrolled', 'scheduled']);
    }

    /**
     * Check if enrollment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if certificate is expired
     */
    public function isCertificateExpired(): bool
    {
        return $this->certificate_expiry && $this->certificate_expiry->isPast();
    }

    /**
     * Check if certificate expires soon (within 30 days)
     */
    public function isCertificateExpiringSoon(): bool
    {
        return $this->certificate_expiry && $this->certificate_expiry->diffInDays(now()) <= 30;
    }
}