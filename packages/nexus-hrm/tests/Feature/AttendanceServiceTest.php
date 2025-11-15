<?php

declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use Illuminate\Support\Str;
use Nexus\Hrm\Models\AttendanceRecord;
use Nexus\Hrm\Services\AttendanceService;

class AttendanceServiceTest extends \Nexus\Hrm\Tests\TestCase
{
    public function test_can_clock_in_an_employee(): void
    {
        $tenant = (string) Str::ulid();
        $employee = (string) Str::ulid();

        $svc = new AttendanceService();
        $record = $svc->clockIn($tenant, $employee, 'Office', 'Morning shift');

        $this->assertInstanceOf(AttendanceRecord::class, $record);
        $this->assertEquals($tenant, $record->tenant_id);
        $this->assertEquals($employee, $record->employee_id);
        $this->assertEquals('Office', $record->location);
        $this->assertEquals('Morning shift', $record->notes);
        $this->assertNull($record->clock_out_at);
    }

    public function test_prevents_double_clock_in(): void
    {
        $tenant = (string) Str::ulid();
        $employee = (string) Str::ulid();

        $svc = new AttendanceService();
        $svc->clockIn($tenant, $employee);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Employee already has an open attendance record.');

        $svc->clockIn($tenant, $employee);
    }

    public function test_can_clock_out_an_employee(): void
    {
        $tenant = (string) Str::ulid();
        $employee = (string) Str::ulid();

        $svc = new AttendanceService();
        $clockIn = $svc->clockIn($tenant, $employee);

        // Simulate time passing
        $this->travel(9)->hours();

        $clockOut = $svc->clockOut($tenant, $employee, 60); // 1 hour break

        $this->assertNotNull($clockOut->clock_out_at);
        $this->assertEquals(60, $clockOut->break_minutes);
        $this->assertEquals(0, $clockOut->overtime_minutes); // 9 hours - 1 hour break = 8 hours, no overtime
    }

    public function test_throws_when_clocking_out_without_clock_in(): void
    {
        $tenant = (string) Str::ulid();
        $employee = (string) Str::ulid();

        $svc = new AttendanceService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No open attendance record found for employee.');

        $svc->clockOut($tenant, $employee);
    }

    public function test_generates_monthly_summary(): void
    {
        $tenant = (string) Str::ulid();
        $employee = (string) Str::ulid();

        $svc = new AttendanceService();

        // Create some attendance records
        for ($i = 1; $i <= 20; $i++) {
            $clockIn = now()->setDay($i)->setHour(9);
            AttendanceRecord::query()->create([
                'tenant_id' => $tenant,
                'employee_id' => $employee,
                'date' => $clockIn->toDateString(),
                'clock_in_at' => $clockIn,
                'clock_out_at' => $clockIn->copy()->addHours(8),
                'break_minutes' => 60,
                'overtime_minutes' => 0,
                'location' => 'Office',
            ]);
        }

        $summary = $svc->generateMonthlySummary($tenant, $employee, (int) now()->format('Y'), (int) now()->format('m'));

        $this->assertEquals(20, $summary['total_days']);
        $this->assertEquals(20, $summary['present_days']);
        $this->assertEquals(0, $summary['absent_days']);
        $this->assertEquals(140.0, $summary['total_worked_hours']); // 20 days * 7 hours (8-1 break)
    }
}
