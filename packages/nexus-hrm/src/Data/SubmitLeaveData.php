<?php

declare(strict_types=1);

namespace Nexus\Hrm\Data;

use Illuminate\Support\Carbon;

class SubmitLeaveData
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $employeeId,
        public readonly string $leaveTypeId,
        public readonly Carbon $startDate,
        public readonly Carbon $endDate,
        public readonly ?string $reason = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            employeeId: $data['employee_id'],
            leaveTypeId: $data['leave_type_id'],
            startDate: Carbon::parse($data['start_date']),
            endDate: Carbon::parse($data['end_date']),
            reason: $data['reason'] ?? null,
        );
    }
}
