<?php

declare(strict_types=1);

namespace Nexus\Hrm\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory, HasUuids;

    protected $connection = 'testing';

    protected $table = 'hrm_attendance_records';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'date',
        'clock_in_at',
        'clock_out_at',
        'break_minutes',
        'overtime_minutes',
        'location',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
        'break_minutes' => 'integer',
        'overtime_minutes' => 'integer',
    ];

    /**
     * Get the employee this attendance record belongs to
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Calculate total worked minutes
     */
    public function getWorkedMinutesAttribute(): float
    {
        if (!$this->clock_out_at) {
            return 0;
        }

        $totalMinutes = $this->clock_in_at->diffInMinutes($this->clock_out_at);
        return $totalMinutes - $this->break_minutes;
    }

    /**
     * Check if attendance is complete (clocked out)
     */
    public function isComplete(): bool
    {
        return !is_null($this->clock_out_at);
    }

    /**
     * Scope to filter by tenant
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
}
