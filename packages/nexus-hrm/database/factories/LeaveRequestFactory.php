<?php

declare(strict_types=1);

namespace Nexus\Hrm\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Nexus\Hrm\Enums\LeaveStatus;
use Nexus\Hrm\Models\LeaveRequest;

class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $end = (clone $start)->modify('+'.random_int(0, 5).' day');
        $days = max(1, (int) $start->diff($end)->format('%a') + 1);

        return [
            'tenant_id' => (string) Str::ulid(),
            'employee_id' => (string) Str::ulid(),
            'leave_type_id' => (string) Str::ulid(),
            'start_date' => $start,
            'end_date' => $end,
            'days' => $days,
            'status' => LeaveStatus::PENDING,
            'approval_chain' => null,
            'workflow_instance_id' => null,
            'reason' => $this->faker->optional()->sentence(),
        ];
    }
}
