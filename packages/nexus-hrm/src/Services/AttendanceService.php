<?php

declare(strict_types=1);

namespace Nexus\Hrm\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Nexus\Hrm\Models\AttendanceRecord;
use Nexus\Hrm\Models\Employee;

/**
 * Attendance Service
 *
 * Handles clock-in/out operations, break tracking, and overtime calculations.
 */
class AttendanceService
{
    /**
     * Clock in an employee
     */
    public function clockIn(string $tenantId, string $employeeId, ?string $location = null, ?string $notes = null): AttendanceRecord
    {
        // Check for existing open attendance (no clock out)
        $existing = AttendanceRecord::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->whereNull('clock_out_at')
            ->first();

        if ($existing) {
            throw new \RuntimeException('Employee already has an open attendance record. Please clock out first.');
        }

        $now = now();
        return AttendanceRecord::query()->create([
            'tenant_id' => $tenantId,
            'employee_id' => $employeeId,
            'date' => $now->toDateString(),
            'clock_in_at' => $now,
            'location' => $location,
            'notes' => $notes,
        ]);
    }

    /**
     * Clock out an employee
     */
    public function clockOut(string $tenantId, string $employeeId, int $breakMinutes = 0, ?string $notes = null): AttendanceRecord
    {
        $record = AttendanceRecord::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->whereNull('clock_out_at')
            ->first();

        if (!$record) {
            throw new \RuntimeException('No open attendance record found for employee.');
        }

        $now = now();
        $workedMinutes = $record->clock_in_at->diffInMinutes($now) - $breakMinutes;

        // Calculate overtime (assuming 8 hours = 480 minutes standard)
        $standardMinutes = 480;
        $overtimeMinutes = max(0, $workedMinutes - $standardMinutes);

        $record->update([
            'clock_out_at' => $now,
            'break_minutes' => $breakMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'notes' => $notes ?: $record->notes,
        ]);

        return $record->fresh();
    }

    /**
     * Get attendance records for employee in date range
     */
    public function getAttendanceRecords(string $tenantId, string $employeeId, string $startDate, string $endDate): Collection
    {
        return AttendanceRecord::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->dateRange($startDate, $endDate)
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Generate monthly attendance summary
     */
    public function generateMonthlySummary(string $tenantId, string $employeeId, int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->toDateString();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $records = $this->getAttendanceRecords($tenantId, $employeeId, $startDate, $endDate);

        $totalDays = $records->count();
        $presentDays = $records->whereNotNull('clock_out_at')->count();
        $absentDays = $totalDays - $presentDays;

        $totalWorkedMinutes = $records->sum('worked_minutes');
        $totalOvertimeMinutes = $records->sum('overtime_minutes');
        $totalBreakMinutes = $records->sum('break_minutes');

        return [
            'employee_id' => $employeeId,
            'period' => "{$year}-{$month}",
            'total_days' => $totalDays,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'total_worked_hours' => round($totalWorkedMinutes / 60, 2),
            'total_overtime_hours' => round($totalOvertimeMinutes / 60, 2),
            'total_break_hours' => round($totalBreakMinutes / 60, 2),
        ];
    }
}