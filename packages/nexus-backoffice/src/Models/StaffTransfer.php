<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Backoffice\Enums\StaffTransferStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Nexus\Backoffice\Exceptions\InvalidTransferException;

/**
 * Staff Transfer Model
 * 
 * Represents a transfer request for moving staff between offices,
 * departments, and reporting lines with flexible timing options.
 * 
 * @property int $id
 * @property int $staff_id
 * @property int $from_office_id
 * @property int $to_office_id
 * @property int|null $from_department_id
 * @property int|null $to_department_id
 * @property int|null $from_supervisor_id
 * @property int|null $to_supervisor_id
 * @property StaffTransferStatus $status
 * @property Carbon $effective_date
 * @property Carbon $requested_at
 * @property Carbon|null $approved_at
 * @property Carbon|null $rejected_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $cancelled_at
 * @property int $requested_by_id
 * @property int|null $approved_by_id
 * @property int|null $rejected_by_id
 * @property int|null $processed_by_id
 * @property string|null $reason
 * @property string|null $notes
 * @property string|null $rejection_reason
 * @property int|null $from_position_id
 * @property int|null $to_position_id
 * @property bool $is_immediate
 * @property array|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @property-read Staff $staff
 * @property-read Office $fromOffice
 * @property-read Office $toOffice
 * @property-read Department|null $fromDepartment
 * @property-read Department|null $toDepartment
 * @property-read Position|null $fromPosition
 * @property-read Position|null $toPosition
 * @property-read Staff|null $fromSupervisor
 * @property-read Staff|null $toSupervisor
 * @property-read Staff $requestedBy
 * @property-read Staff|null $approvedBy
 * @property-read Staff|null $rejectedBy
 * @property-read Staff|null $processedBy
 * 
 * @method static Builder pending()
 * @method static Builder approved()
 * @method static Builder completed()
 * @method static Builder rejected()
 * @method static Builder cancelled()
 * @method static Builder dueForProcessing()
 * @method static Builder forStaff(Staff $staff)
 * @method static Builder betweenOffices(Office $from, Office $to)
 * @method static Builder effectiveOn(Carbon $date)
 */
class StaffTransfer extends Model
{
    use HasFactory;
    
    protected $table = 'backoffice_staff_transfers';
    
    protected $fillable = [
        'staff_id',
        'from_office_id',
        'to_office_id',
        'from_department_id',
        'to_department_id',
        'from_supervisor_id',
        'to_supervisor_id',
        'status',
        'effective_date',
        'requested_at',
        'approved_at',
        'rejected_at',
        'completed_at',
        'cancelled_at',
        'requested_by_id',
        'approved_by_id',
        'rejected_by_id',
        'processed_by_id',
        'reason',
        'notes',
        'rejection_reason',
        'from_position_id',
        'to_position_id',
        'is_immediate',
        'metadata',
    ];
    
    protected $casts = [
        'status' => StaffTransferStatus::class,
        'effective_date' => 'date',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'is_immediate' => 'boolean',
        'metadata' => 'array',
    ];
    
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];
    
    /**
     * Boot the model
     */
    protected static function booted(): void
    {
        static::creating(function (StaffTransfer $transfer) {
            $transfer->validateTransfer();
            
            if (is_null($transfer->requested_at)) {
                $transfer->requested_at = now();
            }
            
            if ($transfer->is_immediate && is_null($transfer->effective_date)) {
                $transfer->effective_date = now();
            }
        });
        
        static::updating(function (StaffTransfer $transfer) {
            if ($transfer->isDirty('status')) {
                $transfer->updateStatusTimestamps();
            }
        });
    }
    
    /**
     * Staff being transferred
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
    
    /**
     * Source office
     */
    public function fromOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'from_office_id');
    }
    
    /**
     * Destination office
     */
    public function toOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'to_office_id');
    }
    
    /**
     * Source department
     */
    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }
    
    /**
     * Destination department
     */
    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }
    
    /**
     * Current supervisor
     */
    public function fromSupervisor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'from_supervisor_id');
    }
    
    /**
     * New supervisor
     */
    public function toSupervisor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'to_supervisor_id');
    }
    
    /**
     * Current position
     */
    public function fromPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'from_position_id');
    }
    
    /**
     * New position
     */
    public function toPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'to_position_id');
    }
    
    /**
     * Who requested the transfer
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'requested_by_id');
    }
    
    /**
     * Who approved the transfer
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by_id');
    }
    
    /**
     * Who rejected the transfer
     */
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'rejected_by_id');
    }
    
    /**
     * Who processed the transfer
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'processed_by_id');
    }
    
    /**
     * Scope: Pending transfers
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', StaffTransferStatus::PENDING);
    }
    
    /**
     * Scope: Approved transfers
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', StaffTransferStatus::APPROVED);
    }
    
    /**
     * Scope: Completed transfers
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', StaffTransferStatus::COMPLETED);
    }
    
    /**
     * Scope: Rejected transfers
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', StaffTransferStatus::REJECTED);
    }
    
    /**
     * Scope: Cancelled transfers
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', StaffTransferStatus::CANCELLED);
    }
    
    /**
     * Scope: Transfers due for processing
     */
    public function scopeDueForProcessing(Builder $query): Builder
    {
        return $query->approved()
            ->where('effective_date', '<=', now()->toDateString());
    }
    
    /**
     * Scope: Transfers for specific staff
     */
    public function scopeForStaff(Builder $query, Staff $staff): Builder
    {
        return $query->where('staff_id', $staff->id);
    }
    
    /**
     * Scope: Transfers between specific offices
     */
    public function scopeBetweenOffices(Builder $query, Office $from, Office $to): Builder
    {
        return $query->where('from_office_id', $from->id)
            ->where('to_office_id', $to->id);
    }
    
    /**
     * Scope: Transfers effective on specific date
     */
    public function scopeEffectiveOn(Builder $query, Carbon $date): Builder
    {
        return $query->where('effective_date', $date->toDateString());
    }
    
    /**
     * Check if transfer is due for processing
     */
    public function isDueForProcessing(): bool
    {
        return $this->status === StaffTransferStatus::APPROVED &&
               ($this->is_immediate || $this->effective_date->isPast());
    }
    
    /**
     * Check if transfer can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return $this->status->canBeModified();
    }
    
    /**
     * Check if transfer can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === StaffTransferStatus::PENDING;
    }
    
    /**
     * Check if transfer can be rejected
     */
    public function canBeRejected(): bool
    {
        return $this->status === StaffTransferStatus::PENDING;
    }
    
    /**
     * Approve the transfer
     */
    public function approve(Staff $approvedBy, ?string $notes = null): void
    {
        if (!$this->canBeApproved()) {
            throw new InvalidTransferException('Transfer cannot be approved in current status: ' . $this->status->value);
        }
        
        $this->update([
            'status' => StaffTransferStatus::APPROVED,
            'approved_at' => now(),
            'approved_by_id' => $approvedBy->id,
            'notes' => $notes ? ($this->notes ? $this->notes . "\n\n" . $notes : $notes) : $this->notes,
        ]);
    }
    
    /**
     * Reject the transfer
     */
    public function reject(Staff $rejectedBy, string $reason): void
    {
        if (!$this->canBeRejected()) {
            throw new InvalidTransferException('Transfer cannot be rejected in current status: ' . $this->status->value);
        }
        
        $this->update([
            'status' => StaffTransferStatus::REJECTED,
            'rejected_at' => now(),
            'rejected_by_id' => $rejectedBy->id,
            'rejection_reason' => $reason,
        ]);
    }
    
    /**
     * Cancel the transfer
     */
    public function cancel(Staff $cancelledBy, ?string $reason = null): void
    {
        if (!$this->canBeCancelled()) {
            throw new InvalidTransferException('Transfer cannot be cancelled in current status: ' . $this->status->value);
        }
        
        $this->update([
            'status' => StaffTransferStatus::CANCELLED,
            'cancelled_at' => now(),
            'processed_by_id' => $cancelledBy->id,
            'notes' => $reason ? ($this->notes ? $this->notes . "\n\nCancellation: " . $reason : "Cancellation: " . $reason) : $this->notes,
        ]);
    }
    
    /**
     * Complete the transfer
     */
    public function complete(Staff $processedBy): void
    {
        if (!$this->status->canBeProcessed()) {
            throw new InvalidTransferException('Transfer cannot be completed in current status: ' . $this->status->value);
        }
        
        if (!$this->isDueForProcessing()) {
            throw new InvalidTransferException('Transfer is not due for processing yet');
        }
        
        $this->update([
            'status' => StaffTransferStatus::COMPLETED,
            'completed_at' => now(),
            'processed_by_id' => $processedBy->id,
        ]);
        
        // Update the staff record
        $this->applyTransferToStaff();
    }
    
    /**
     * Apply the transfer changes to the staff record
     */
    protected function applyTransferToStaff(): void
    {
        $updates = [
            'office_id' => $this->to_office_id,
        ];
        
        if ($this->to_department_id !== null) {
            $updates['department_id'] = $this->to_department_id;
        }
        
        if ($this->to_supervisor_id !== null) {
            $updates['supervisor_id'] = $this->to_supervisor_id;
        }
        
        if ($this->to_position_id !== null) {
            $updates['position_id'] = $this->to_position_id;
        }
        
        $this->staff->update($updates);
    }
    
    /**
     * Validate the transfer request
     */
    protected function validateTransfer(): void
    {
        // Cannot transfer to the same office
        if ($this->from_office_id === $this->to_office_id) {
            throw new InvalidTransferException('Cannot transfer staff to the same office');
        }
        
        // Check if staff is already being transferred
        $existingTransfer = static::forStaff($this->staff)
            ->whereIn('status', [StaffTransferStatus::PENDING, StaffTransferStatus::APPROVED])
            ->exists();
            
        if ($existingTransfer) {
            throw new InvalidTransferException('Staff already has a pending or approved transfer');
        }
        
        // Validate effective date
        if (!$this->is_immediate && $this->effective_date->isToday()) {
            throw new InvalidTransferException('Effective date must be tomorrow or in the future for scheduled transfers');
        }
        
        if ($this->effective_date->lt(Carbon::today())) {
            throw new InvalidTransferException('Effective date cannot be in the past');
        }
        
        // Validate supervisor change doesn't create circular reference
        if ($this->to_supervisor_id) {
            $this->validateSupervisorChange();
        }
    }
    
    /**
     * Validate supervisor change doesn't create circular reference
     */
    protected function validateSupervisorChange(): void
    {
        if ($this->to_supervisor_id === $this->staff_id) {
            throw new InvalidTransferException('Staff cannot be their own supervisor');
        }
        
        // Check if the new supervisor would report to this staff (circular reference)
        $toSupervisor = Staff::find($this->to_supervisor_id);
        if ($toSupervisor && $toSupervisor->reportsTo($this->staff)) {
            throw new InvalidTransferException('Cannot assign supervisor who reports to this staff member');
        }
    }
    
    /**
     * Update status timestamps when status changes
     */
    protected function updateStatusTimestamps(): void
    {
        $now = now();
        
        match ($this->status) {
            StaffTransferStatus::APPROVED => $this->approved_at = $now,
            StaffTransferStatus::REJECTED => $this->rejected_at = $now,
            StaffTransferStatus::COMPLETED => $this->completed_at = $now,
            StaffTransferStatus::CANCELLED => $this->cancelled_at = $now,
            default => null,
        };
    }
    
    /**
     * Get the days until effective date
     */
    protected function daysUntilEffective(): Attribute
    {
        return Attribute::make(
            get: fn (): int => (int) now()->diffInDays($this->effective_date, false),
        );
    }
    
    /**
     * Check if this is an immediate transfer (effective today)
     */
    public function isImmediate(): bool
    {
        return $this->effective_date->isToday();
    }
    
    /**
     * Get summary of changes
     */
    public function getChangesSummary(): array
    {
        $changes = [];
        
        $changes['office'] = [
            'from' => $this->fromOffice->name,
            'to' => $this->toOffice->name,
        ];
        
        if ($this->from_department_id !== $this->to_department_id) {
            $changes['department'] = [
                'from' => $this->fromDepartment?->name,
                'to' => $this->toDepartment?->name,
            ];
        }
        
        if ($this->from_supervisor_id !== $this->to_supervisor_id) {
            $changes['supervisor'] = [
                'from' => $this->fromSupervisor?->full_name,
                'to' => $this->toSupervisor?->full_name,
            ];
        }
        
        if ($this->from_position !== $this->to_position && $this->to_position) {
            $changes['position'] = [
                'from' => $this->from_position,
                'to' => $this->to_position,
            ];
        }
        
        return $changes;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Nexus\Backoffice\Database\Factories\StaffTransferFactory
    {
        return \Nexus\Backoffice\Database\Factories\StaffTransferFactory::new();
    }
}