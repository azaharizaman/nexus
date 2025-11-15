<?php

declare(strict_types=1);

namespace Nexus\Hrm\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Hrm\Enums\ContractType;

/**
 * Employment Contract Model
 * 
 * Tracks employment contracts with start/end dates, position, and terms.
 * 
 * @property string $id
 * @property string $tenant_id
 * @property string $employee_id
 * @property ContractType $contract_type
 * @property \DateTimeInterface $start_date
 * @property \DateTimeInterface|null $end_date
 * @property int|null $probation_period_days
 * @property \DateTimeInterface|null $probation_end_date
 * @property string $position
 * @property string|null $department_id
 * @property string|null $reporting_to_employee_id
 * @property string|null $employment_grade
 * @property float|null $salary
 * @property string|null $salary_currency
 * @property array|null $benefits
 * @property string|null $work_schedule
 * @property int|null $standard_work_hours_per_week
 * @property bool $is_current
 * @property string|null $contract_document_path
 * @property array|null $terms_and_conditions
 * @property \DateTimeInterface|null $created_at
 * @property \DateTimeInterface|null $updated_at
 */
class EmploymentContract extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'hrm_employment_contracts';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'contract_type',
        'start_date',
        'end_date',
        'probation_period_days',
        'probation_end_date',
        'position',
        'department_id',
        'reporting_to_employee_id',
        'employment_grade',
        'salary',
        'salary_currency',
        'benefits',
        'work_schedule',
        'standard_work_hours_per_week',
        'is_current',
        'contract_document_path',
        'terms_and_conditions',
    ];

    protected $casts = [
        'contract_type' => ContractType::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'probation_end_date' => 'date',
        'probation_period_days' => 'integer',
        'salary' => 'decimal:2',
        'benefits' => 'array',
        'standard_work_hours_per_week' => 'integer',
        'is_current' => 'boolean',
        'terms_and_conditions' => 'array',
    ];

    /**
     * Get the employee this contract belongs to
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Check if contract has ended
     */
    public function hasEnded(): bool
    {
        if (!$this->end_date) {
            return false;
        }

        return $this->end_date->isPast();
    }

    /**
     * Check if probation period has ended
     */
    public function isProbationComplete(): bool
    {
        if (!$this->probation_end_date) {
            return true; // No probation
        }

        return $this->probation_end_date->isPast();
    }

    /**
     * Get days remaining in probation
     */
    public function probationDaysRemaining(): int
    {
        if (!$this->probation_end_date || $this->isProbationComplete()) {
            return 0;
        }

        return now()->diffInDays($this->probation_end_date, false);
    }

    /**
     * Get contract duration in days
     */
    public function durationInDays(): ?int
    {
        if (!$this->end_date) {
            return null; // Open-ended contract
        }

        return $this->start_date->diffInDays($this->end_date);
    }

    /**
     * Scope to get current contracts
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope to get active contracts (current or within date range)
     */
    public function scopeActive($query)
    {
        return $query->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Scope to filter by tenant
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
