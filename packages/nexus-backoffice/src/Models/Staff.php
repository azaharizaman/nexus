<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Backoffice\Enums\StaffStatus;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Staff Model
 * 
 * Represents staff/employees that can belong to offices and/or departments.
 * Staff can belong to one office and/or one department, and can be part of multiple units.
 * Supports hierarchical reporting relationships for organizational charts.
 * 
 * @property int $id
 * @property string $employee_id
 * @property string $first_name
 * @property string $last_name
 * @property string $full_name
 * @property string|null $email
 * @property string|null $phone
      * @property int|null $office_id
     * @property int|null $department_id
     * @property int|null $position_id
     * @property int|null $supervisor_id
     * @property \Illuminate\Support\Carbon|null $hire_date
 * @property \Illuminate\Support\Carbon|null $resignation_date
 * @property string|null $resignation_reason
 * @property \Illuminate\Support\Carbon|null $resigned_at
 * @property \Nexus\Backoffice\Enums\StaffStatus $status
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * 
      * @property-read Office|null $office
     * @property-read Department|null $department
     * @property-read Position|null $position
     * @property-read Staff|null $supervisor
     * @property-read \Illuminate\Database\Eloquent\Collection<int, Staff> $subordinates
     * @property-read \Illuminate\Database\Eloquent\Collection<int, Unit> $units
 */
class Staff extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'backoffice_staff';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'office_id',
        'department_id',
        'position_id',
        'supervisor_id',
        'hire_date',
        'resignation_date',
        'resignation_reason',
        'resigned_at',
        'status',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'hire_date' => 'date',
        'resignation_date' => 'date',
        'resigned_at' => 'datetime',
        'status' => StaffStatus::class,
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = ['full_name'];

    /**
     * Get the office that this staff belongs to.
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * Get the department that this staff belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the position of this staff.
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Get the units this staff belongs to.
     */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(
            Unit::class,
            'backoffice_staff_unit',
            'staff_id',
            'unit_id'
        );
    }

    /**
     * Get the supervisor of this staff.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'supervisor_id');
    }

    /**
     * Get the subordinates of this staff.
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(Staff::class, 'supervisor_id');
    }

    /**
     * Get the staff's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get the company through office or department.
     */
    public function getCompany(): ?Company
    {
        if ($this->office) {
            return $this->office->company;
        }
        
        if ($this->department) {
            return $this->department->company;
        }
        
        return null;
    }

    /**
     * Check if staff is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if staff belongs to an office.
     */
    public function hasOffice(): bool
    {
        return !is_null($this->office_id);
    }

    /**
     * Check if staff belongs to a department.
     */
    public function hasDepartment(): bool
    {
        return !is_null($this->department_id);
    }

    /**
     * Check if staff has a position assigned.
     */
    public function hasPosition(): bool
    {
        return !is_null($this->position_id);
    }

    /**
     * Get the effective department for this staff.
     * 
     * Returns the staff's department if set, otherwise returns the position's
     * default department if the position has one.
     * 
     * @return Department|null
     */
    public function getEffectiveDepartment(): ?Department
    {
        // Staff's own department takes precedence
        if ($this->department_id !== null) {
            return $this->department;
        }

        // Fall back to position's default department
        if ($this->position && $this->position->department_id !== null) {
            return $this->position->department;
        }

        return null;
    }

    /**
     * Get the effective department ID for this staff.
     * 
     * @return int|null
     */
    public function getEffectiveDepartmentId(): ?int
    {
        // Staff's own department takes precedence
        if ($this->department_id !== null) {
            return $this->department_id;
        }

        // Fall back to position's default department
        if ($this->position && $this->position->department_id !== null) {
            return $this->position->department_id;
        }

        return null;
    }

    /**
     * Scope to get only active staff.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by office.
     */
    public function scopeInOffice($query, $officeId)
    {
        return $query->where('office_id', $officeId);
    }

    /**
     * Scope to filter by department.
     */
    public function scopeInDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope to filter by unit.
     */
    public function scopeInUnit($query, $unitId)
    {
        return $query->whereHas('units', function ($q) use ($unitId) {
            $q->where('unit_id', $unitId);
        });
    }

    /**
     * Scope to search by name.
     */
    public function scopeSearchByName($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"]);
        });
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, StaffStatus $status)
    {
        return $query->where('status', $status->value);
    }

    /**
     * Scope to get staff with pending resignations.
     */
    public function scopePendingResignation($query)
    {
        return $query->whereNotNull('resignation_date')
                    ->whereNull('resigned_at')
                    ->where('status', '!=', StaffStatus::RESIGNED->value);
    }

    /**
     * Scope to get resigned staff.
     */
    public function scopeResigned($query)
    {
        return $query->where('status', StaffStatus::RESIGNED->value)
                    ->whereNotNull('resigned_at');
    }

    /**
     * Schedule staff resignation.
     */
    public function scheduleResignation(\DateTime $resignationDate, ?string $reason = null): self
    {
        $this->update([
            'resignation_date' => $resignationDate,
            'resignation_reason' => $reason,
        ]);

        return $this;
    }

    /**
     * Process resignation (mark as resigned).
     */
    public function processResignation(): self
    {
        $this->update([
            'status' => StaffStatus::RESIGNED,
            'resigned_at' => now(),
            'is_active' => false,
        ]);

        return $this;
    }

    /**
     * Cancel scheduled resignation.
     */
    public function cancelResignation(): self
    {
        $this->update([
            'resignation_date' => null,
            'resignation_reason' => null,
        ]);

        return $this;
    }

    /**
     * Check if staff has pending resignation.
     */
    public function hasPendingResignation(): bool
    {
        return !is_null($this->resignation_date) && 
               is_null($this->resigned_at) && 
               !$this->status->isResigned();
    }

    /**
     * Check if resignation is due (date has passed).
     */
    public function isResignationDue(): bool
    {
        return $this->hasPendingResignation() && 
               $this->resignation_date <= now()->toDateString();
    }

    /**
     * Check if staff is resigned.
     */
    public function isResigned(): bool
    {
        return $this->status->isResigned();
    }

    /**
     * Get days until resignation.
     */
    public function getDaysUntilResignation(): ?int
    {
        if (!$this->hasPendingResignation()) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->resignation_date->startOfDay(), false);
    }

    // ==========================================
    // REPORTING LINE / ORGANIZATIONAL CHART METHODS
    // ==========================================

    /**
     * Check if this staff member has a supervisor.
     */
    public function hasSupervisor(): bool
    {
        return !is_null($this->supervisor_id);
    }

    /**
     * Check if this staff member has subordinates.
     */
    public function hasSubordinates(): bool
    {
        return $this->subordinates()->exists();
    }

    /**
     * Check if this staff member is a manager (has subordinates).
     */
    public function isManager(): bool
    {
        return $this->hasSubordinates();
    }

    /**
     * Check if this staff member is a top-level manager (no supervisor).
     */
    public function isTopLevel(): bool
    {
        return !$this->hasSupervisor();
    }

    /**
     * Get all ancestors (supervisors up the chain).
     */
    public function getAncestors(): \Illuminate\Database\Eloquent\Collection
    {
        $ancestors = $this->newCollection();
        $current = $this->supervisor;

        while ($current) {
            $ancestors->push($current);
            $current = $current->supervisor;
        }

        return $ancestors;
    }

    /**
     * Get all descendants (subordinates down the chain).
     */
    public function getDescendants(): \Illuminate\Database\Eloquent\Collection
    {
        $descendants = $this->newCollection();
        $this->loadDescendantsRecursive($descendants);
        return $descendants;
    }

    /**
     * Recursively load all descendants.
     */
    private function loadDescendantsRecursive(\Illuminate\Database\Eloquent\Collection $descendants): void
    {
        $directSubordinates = $this->subordinates;
        
        foreach ($directSubordinates as $subordinate) {
            $descendants->push($subordinate);
            $subordinate->loadDescendantsRecursive($descendants);
        }
    }

    /**
     * Get the reporting path from this staff to root.
     */
    public function getReportingPath(): \Illuminate\Database\Eloquent\Collection
    {
        $path = $this->newCollection([$this]);
        $current = $this->supervisor;

        while ($current) {
            $path->push($current);
            $current = $current->supervisor;
        }

        return $path;
    }

    /**
     * Get the top-level manager (CEO/President).
     */
    public function getTopLevelManager(): ?Staff
    {
        $ancestors = $this->getAncestors();
        return $ancestors->last() ?? ($this->isTopLevel() ? $this : null);
    }

    /**
     * Get reporting level (distance from top).
     */
    public function getReportingLevel(): int
    {
        return $this->getAncestors()->count();
    }

    /**
     * Get organizational chart as nested array starting from this staff.
     */
    public function getOrganizationalChart(): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'name' => $this->full_name,
            'position' => $this->position,
            'email' => $this->email,
            'office' => $this->office?->name,
            'department' => $this->department?->name,
            'level' => $this->getReportingLevel(),
            'subordinates' => $this->subordinates->map(function ($subordinate) {
                return $subordinate->getOrganizationalChart();
            })->toArray(),
        ];
    }

    /**
     * Get peers (staff with same supervisor).
     */
    public function getPeers(): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->hasSupervisor()) {
            // Top-level staff - peers are other top-level staff in same company
            $company = $this->getCompany();
            if (!$company) {
                return collect();
            }

            return Staff::whereNull('supervisor_id')
                        ->where('id', '!=', $this->id)
                        ->where(function ($query) use ($company) {
                            $query->whereHas('office', function ($q) use ($company) {
                                $q->where('company_id', $company->id);
                            })->orWhereHas('department', function ($q) use ($company) {
                                $q->where('company_id', $company->id);
                            });
                        })
                        ->get();
        }

        return $this->supervisor->subordinates()
                    ->where('id', '!=', $this->id)
                    ->get();
    }

    /**
     * Check if this staff reports to the given staff (directly or indirectly).
     */
    public function reportsTo(Staff $potentialSupervisor): bool
    {
        return $this->getAncestors()->contains('id', $potentialSupervisor->id);
    }

    /**
     * Check if this staff manages the given staff (directly or indirectly).
     */
    public function manages(Staff $potentialSubordinate): bool
    {
        return $this->getDescendants()->contains('id', $potentialSubordinate->id);
    }

    /**
     * Check if setting the given staff as supervisor would create a circular reference.
     */
    public function wouldCreateCircularReference(Staff $potentialSupervisor): bool
    {
        // Can't report to self
        if ($this->id === $potentialSupervisor->id) {
            return true;
        }

        // Can't report to someone who reports to you
        return $potentialSupervisor->reportsTo($this);
    }

    /**
     * Set supervisor with validation.
     */
    public function setSupervisor(?Staff $supervisor): self
    {
        if ($supervisor && $this->wouldCreateCircularReference($supervisor)) {
            throw new \InvalidArgumentException('Cannot set supervisor: would create circular reference');
        }

        $this->supervisor_id = $supervisor?->id;
        $this->save();

        return $this;
    }

    /**
     * Get team size (number of direct and indirect subordinates).
     */
    public function getTeamSize(): int
    {
        return $this->getDescendants()->count();
    }

    /**
     * Get span of control (number of direct subordinates).
     */
    public function getSpanOfControl(): int
    {
        return $this->subordinates()->count();
    }

    /**
     * Scope to get top-level staff (no supervisor).
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('supervisor_id');
    }

    /**
     * Scope to get managers (staff with subordinates).
     */
    public function scopeManagers($query)
    {
        return $query->has('subordinates');
    }

    /**
     * Scope to get staff at specific reporting level.
     */
    public function scopeAtLevel($query, int $level)
    {
        if ($level === 0) {
            return $query->whereNull('supervisor_id');
        }

        // This is more complex and might require raw SQL for efficiency
        // For now, we'll use a simpler approach
        return $query->whereHas('supervisor', function ($q) use ($level) {
            if ($level === 1) {
                $q->whereNull('supervisor_id');
            }
        });
    }

    /**
     * Scope to get staff who report to a specific supervisor.
     */
    public function scopeReportsTo($query, Staff $supervisor)
    {
        return $query->where('supervisor_id', $supervisor->id);
    }
    
    // ===== TRANSFER METHODS =====
    
    /**
     * Get the transfers for this staff member.
     */
    public function transfers(): HasMany
    {
        return $this->hasMany(StaffTransfer::class);
    }
    
    /**
     * Get the active (pending or approved) transfer for this staff.
     */
    public function activeTransfer(): HasMany
    {
        return $this->transfers()
            ->whereIn('status', ['pending', 'approved']);
    }
    
    /**
     * Get the transfer history for this staff.
     */
    public function transferHistory(): HasMany
    {
        return $this->transfers()
            ->whereIn('status', ['completed', 'rejected', 'cancelled'])
            ->orderBy('created_at', 'desc');
    }
    
    /**
     * Check if staff has an active transfer.
     */
    public function hasActiveTransfer(): bool
    {
        return $this->activeTransfer()->exists();
    }
    
    /**
     * Check if staff can be transferred.
     */
    public function canBeTransferred(): bool
    {
        return $this->is_active && 
               $this->status === StaffStatus::ACTIVE && 
               !$this->hasActiveTransfer();
    }
    
    /**
     * Request a transfer for this staff member.
     */
    public function requestTransfer(
        Office $toOffice,
        Staff $requestedBy,
        ?Carbon $effectiveDate = null,
        ?Department $toDepartment = null,
        ?Staff $toSupervisor = null,
        ?Position $toPosition = null,
        ?string $reason = null,
        bool $isImmediate = false
    ): StaffTransfer {
        // Load current relationships to capture current state
        $this->load(['office', 'department', 'supervisor']);
        
        $transferData = [
            'staff_id' => $this->id,
            'from_office_id' => $this->office_id,
            'to_office_id' => $toOffice->id,
            'from_department_id' => $this->department_id,
            'to_department_id' => $toDepartment?->id,
            'from_supervisor_id' => $this->supervisor_id,
            'to_supervisor_id' => $toSupervisor?->id,
            'from_position_id' => $this->position_id,
            'to_position_id' => $toPosition?->id,
            'effective_date' => $effectiveDate ?? ($isImmediate ? now() : now()->addDays(30)),
            'requested_by_id' => $requestedBy->id,
            'reason' => $reason,
            'is_immediate' => $isImmediate,
            'requested_at' => now(),
        ];
        
        return StaffTransfer::create($transferData);
    }
    
    /**
     * Request an immediate transfer for this staff member.
     */
    public function requestImmediateTransfer(
        Office $toOffice,
        Staff $requestedBy,
        ?Department $toDepartment = null,
        ?Staff $toSupervisor = null,
        ?Position $toPosition = null,
        ?string $reason = null
    ): StaffTransfer {
        return $this->requestTransfer(
            toOffice: $toOffice,
            requestedBy: $requestedBy,
            effectiveDate: now(),
            toDepartment: $toDepartment,
            toSupervisor: $toSupervisor,
            toPosition: $toPosition,
            reason: $reason,
            isImmediate: true
        );
    }
    
    /**
     * Get the latest transfer for this staff.
     */
    public function latestTransfer(): ?StaffTransfer
    {
        return $this->transfers()
            ->latest('created_at')
            ->first();
    }
    
    /**
     * Get completed transfers count.
     */
    public function getCompletedTransfersCount(): int
    {
        return $this->transfers()
            ->where('status', 'completed')
            ->count();
    }
    
    /**
     * Get the last completed transfer.
     */
    public function lastCompletedTransfer(): ?StaffTransfer
    {
        return $this->transfers()
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first();
    }
    
    /**
     * Check if staff has been transferred within a given period.
     */
    public function hasRecentTransfer(int $days = 90): bool
    {
        return $this->transfers()
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->subDays($days))
            ->exists();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Nexus\Backoffice\Database\Factories\StaffFactory
    {
        return \Nexus\Backoffice\Database\Factories\StaffFactory::new();
    }
}