<?php

declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use Illuminate\Support\Str;
use Nexus\Hrm\Data\SubmitLeaveData;
use Nexus\Hrm\Enums\LeaveStatus;
use Nexus\Hrm\Models\LeaveEntitlement;
use Nexus\Hrm\Models\LeaveRequest;
use Nexus\Hrm\Services\LeaveManagementService;

class LeaveManagementServiceTest extends \Nexus\Hrm\Tests\TestCase
{
    public function test_approves_leave_when_days_under_auto_approve_threshold(): void
    {
        $this->app['config']->set('hrm.leave.auto_approve_threshold_days', 3);
        $this->app['config']->set('hrm.leave.enable_negative_balance', false);

        $tenant = (string) Str::ulid();
        $employee = (string) Str::ulid();
        $leaveType = (string) Str::ulid();

        LeaveEntitlement::query()->create([
            'tenant_id' => $tenant,
            'employee_id' => $employee,
            'leave_type_id' => $leaveType,
            'year' => (int) now()->format('Y'),
            'entitled_days' => 5,
            'used_days' => 0,
            'carried_forward_days' => 0,
        ]);

        $svc = $this->app->make(LeaveManagementService::class);
        $req = $svc->submit(SubmitLeaveData::fromArray([
            'tenant_id' => $tenant,
            'employee_id' => $employee,
            'leave_type_id' => $leaveType,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(1)->toDateString(),
            'reason' => 'Test',
        ]));

        $this->assertInstanceOf(LeaveRequest::class, $req);
        $this->assertEquals(LeaveStatus::APPROVED, $req->status);
        $this->assertSame(2.0, (float) $req->days);
    }

    public function test_sets_pending_when_days_exceed_auto_approve_threshold(): void
    {
        $this->app['config']->set('hrm.leave.auto_approve_threshold_days', 1);
        $this->app['config']->set('hrm.leave.enable_negative_balance', false);

        $tenant = (string) Str::ulid();
        $employee = (string) Str::ulid();
        $leaveType = (string) Str::ulid();

        LeaveEntitlement::query()->create([
            'tenant_id' => $tenant,
            'employee_id' => $employee,
            'leave_type_id' => $leaveType,
            'year' => (int) now()->format('Y'),
            'entitled_days' => 10,
            'used_days' => 0,
            'carried_forward_days' => 0,
        ]);

        $svc = $this->app->make(LeaveManagementService::class);
        $req = $svc->submit(SubmitLeaveData::fromArray([
            'tenant_id' => $tenant,
            'employee_id' => $employee,
            'leave_type_id' => $leaveType,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'reason' => null,
        ]));

        $this->assertEquals(LeaveStatus::PENDING, $req->status);
        $this->assertSame(3.0, (float) $req->days);
    }

    public function test_throws_when_insufficient_balance_and_negative_not_allowed(): void
    {
        $this->app['config']->set('hrm.leave.enable_negative_balance', false);

        $tenant = (string) Str::ulid();
        $employee = (string) Str::ulid();
        $leaveType = (string) Str::ulid();

        LeaveEntitlement::query()->create([
            'tenant_id' => $tenant,
            'employee_id' => $employee,
            'leave_type_id' => $leaveType,
            'year' => (int) now()->format('Y'),
            'entitled_days' => 1,
            'used_days' => 0,
            'carried_forward_days' => 0,
        ]);

        $svc = $this->app->make(LeaveManagementService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Insufficient leave balance.');

        $svc->submit(SubmitLeaveData::fromArray([
            'tenant_id' => $tenant,
            'employee_id' => $employee,
            'leave_type_id' => $leaveType,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
        ]));
    }

    public function test_allows_negative_balance_up_to_max_when_enabled(): void
    {
        $this->app['config']->set('hrm.leave.enable_negative_balance', true);
        $this->app['config']->set('hrm.leave.max_negative_balance_days', 2);

        $tenant = (string) Str::ulid();
        $employee = (string) Str::ulid();
        $leaveType = (string) Str::ulid();

        LeaveEntitlement::query()->create([
            'tenant_id' => $tenant,
            'employee_id' => $employee,
            'leave_type_id' => $leaveType,
            'year' => (int) now()->format('Y'),
            'entitled_days' => 1,
            'used_days' => 0,
            'carried_forward_days' => 0,
        ]);

        $svc = $this->app->make(LeaveManagementService::class);
        $req = $svc->submit(SubmitLeaveData::fromArray([
            'tenant_id' => $tenant,
            'employee_id' => $employee,
            'leave_type_id' => $leaveType,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
        ]));

        $this->assertEquals(LeaveStatus::PENDING, $req->status);
        $this->assertSame(3.0, (float) $req->days);
    }
}
