<?php

declare(strict_types=1);

namespace Nexus\Hrm\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Nexus\Hrm\Contracts\WorkflowServiceContract;
use Nexus\Hrm\Data\SubmitLeaveData;
use Nexus\Hrm\Enums\LeaveStatus;
use Nexus\Hrm\Models\LeaveEntitlement;
use Nexus\Hrm\Models\LeaveRequest;

class LeaveManagementService
{
    public function __construct(
        private readonly ?WorkflowServiceContract $workflow = null,
    ) {}

    /**
     * Submit a leave request, enforcing basic entitlement checks.
     */
    public function submit(SubmitLeaveData $data): LeaveRequest
    {
        $days = $this->calculateDays($data->startDate, $data->endDate);

        return DB::transaction(function () use ($data, $days) {
            // Load entitlement for current year
            $year = (int) $data->startDate->format('Y');
            $entitlement = LeaveEntitlement::query()
                ->where('tenant_id', $data->tenantId)
                ->where('employee_id', $data->employeeId)
                ->where('leave_type_id', $data->leaveTypeId)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            $enableNegative = (bool) config('hrm.leave.enable_negative_balance', false);
            $maxNegative = (float) config('hrm.leave.max_negative_balance_days', 0);

            if ($entitlement) {
                $balance = (float) $entitlement->entitled_days - (float) $entitlement->used_days + (float) $entitlement->carried_forward_days;
                if (!$enableNegative && $days > $balance) {
                    throw new \RuntimeException('Insufficient leave balance.');
                }
                if ($enableNegative && $days - $balance > $maxNegative) {
                    throw new \RuntimeException('Requested days exceed maximum negative balance.');
                }
            }

            $autoApproveThreshold = (float) config('hrm.leave.auto_approve_threshold_days', 0);
            $status = ($autoApproveThreshold > 0 && $days <= $autoApproveThreshold)
                ? LeaveStatus::APPROVED
                : LeaveStatus::PENDING;

            $leave = new LeaveRequest([
                'tenant_id' => $data->tenantId,
                'employee_id' => $data->employeeId,
                'leave_type_id' => $data->leaveTypeId,
                'start_date' => $data->startDate,
                'end_date' => $data->endDate,
                'days' => $days,
                'status' => $status,
                'reason' => $data->reason,
            ]);
            $leave->save();

            // Start workflow if required and not auto-approved
            $requireApproval = (bool) config('hrm.leave.require_workflow_approval', true);
            if ($requireApproval && $status === LeaveStatus::PENDING && $this->workflow) {
                $instance = $this->workflow->submit('hrm.leave_request', [
                    'leave_request_id' => $leave->id,
                    'employee_id' => $leave->employee_id,
                    'tenant_id' => $leave->tenant_id,
                    'days' => $leave->days,
                ]);
                if ($instance) {
                    $leave->workflow_instance_id = $instance;
                    $leave->save();
                }
            }

            return $leave;
        });
    }

    private function calculateDays(Carbon $start, Carbon $end): float
    {
        $diff = $start->diffInDays($end) + 1; // inclusive
        return (float) $diff;
    }
}
