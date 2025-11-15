<?php

declare(strict_types=1);

namespace Nexus\Hrm\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Nexus\Hrm\Models\LeaveEntitlement;

class LeaveEntitlementFactory extends Factory
{
    protected $model = LeaveEntitlement::class;

    public function definition(): array
    {
        return [
            'tenant_id' => (string) Str::ulid(),
            'employee_id' => (string) Str::ulid(),
            'leave_type_id' => (string) Str::ulid(),
            'year' => (int) now()->format('Y'),
            'entitled_days' => 12,
            'used_days' => 0,
            'carried_forward_days' => 0,
        ];
    }
}
