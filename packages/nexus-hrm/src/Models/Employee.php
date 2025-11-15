<?php

declare(strict_types=1);

namespace Nexus\Hrm\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Hrm\Enums\EmploymentStatus;

/**
 * Employee Model
 * 
 * Central employee master data entity for HRM package.
 * Follows atomic package principles with BelongsToTenant trait.
 * 
 * @property string $id
 * @property string $tenant_id
 * @property string $employee_number
 * @property string $first_name
 * @property string $last_name
 * @property string|null $middle_name
 * @property string $email
 * @property string|null $phone
 * @property string|null $mobile
 * @property \DateTimeInterface|null $date_of_birth
 * @property string|null $national_id
 * @property string|null $passport_number
 * @property string|null $gender
 * @property string|null $marital_status
 * @property array|null $emergency_contacts
 * @property array|null $dependents
 * @property EmploymentStatus $employment_status
 * @property \DateTimeInterface|null $hire_date
 * @property \DateTimeInterface|null $termination_date
 * @property string|null $termination_reason
 * @property \DateTimeInterface|null $created_at
 * @property \DateTimeInterface|null $updated_at
 * @property \DateTimeInterface|null $deleted_at
 */
class Employee extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'hrm_employees';

    protected $fillable = [
        'tenant_id',
        'employee_number',
        'first_name',
        'last_name',
        'middle_name',
        'email',
        'phone',
        'mobile',
        'date_of_birth',
        'national_id',
        'passport_number',
        'gender',
        'marital_status',
        'emergency_contacts',
        'dependents',
        'employment_status',
        'hire_date',
        'termination_date',
        'termination_reason',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'emergency_contacts' => 'array',
        'dependents' => 'array',
        'employment_status' => EmploymentStatus::class,
        'hire_date' => 'date',
        'termination_date' => 'date',
    ];

    /**
     * Get full name attribute
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ]);

        return implode(' ', $parts);
    }

    /**
     * Check if employee is currently employed
     */
    public function isEmployed(): bool
    {
        return $this->employment_status->isEmployed();
    }

    /**
     * Check if employee is on probation
     */
    public function isOnProbation(): bool
    {
        return $this->employment_status === EmploymentStatus::PROBATION;
    }

    /**
     * Check if employee is permanent
     */
    public function isPermanent(): bool
    {
        return $this->employment_status === EmploymentStatus::PERMANENT;
    }

    /**
     * Get the current employment contract
     */
    public function currentContract(): HasOne
    {
        return $this->hasOne(EmploymentContract::class)
            ->where('is_current', true)
            ->latest('start_date');
    }

    /**
     * Get all employment contracts
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(EmploymentContract::class)
            ->orderBy('start_date', 'desc');
    }

    /**
     * Get leave entitlements
     */
    public function leaveEntitlements(): HasMany
    {
        return $this->hasMany(LeaveEntitlement::class);
    }

    /**
     * Get leave requests
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Get attendance records
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * Get performance reviews
     */
    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class);
    }

    /**
     * Scope to filter by employment status
     */
    public function scopeStatus($query, EmploymentStatus $status)
    {
        return $query->where('employment_status', $status);
    }

    /**
     * Scope to get active employees
     */
    public function scopeActive($query)
    {
        return $query->whereIn('employment_status', [
            EmploymentStatus::ACTIVE,
            EmploymentStatus::PROBATION,
            EmploymentStatus::PERMANENT,
        ]);
    }

    /**
     * Scope to filter by tenant
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
