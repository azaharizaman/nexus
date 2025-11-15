<?php

declare(strict_types=1);

namespace Nexus\Hrm\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Nexus\Hrm\Enums\ContractType;
use Nexus\Hrm\Models\EmploymentContract;

class EmploymentContractFactory extends Factory
{
    protected $model = EmploymentContract::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-1 year', 'now');
        $end = $this->faker->boolean(30) ? $this->faker->dateTimeBetween($start, '+1 year') : null;

        return [
            'tenant_id' => (string) Str::ulid(),
            'employee_id' => (string) Str::ulid(),
            'contract_type' => $this->faker->randomElement([ContractType::PERMANENT, ContractType::CONTRACT, ContractType::TEMPORARY]),
            'start_date' => $start,
            'end_date' => $end,
            'probation_period_days' => $this->faker->optional()->numberBetween(30, 120),
            'probation_end_date' => null,
            'position' => $this->faker->jobTitle(),
            'department_id' => null,
            'reporting_to_employee_id' => null,
            'employment_grade' => $this->faker->optional()->randomElement(['J1','J2','S1','S2','M1','M2']),
            'salary' => $this->faker->optional()->randomFloat(2, 2000, 20000),
            'salary_currency' => $this->faker->optional()->randomElement(['USD','EUR','MYR']),
            'benefits' => null,
            'work_schedule' => $this->faker->optional()->randomElement(['standard','shift']),
            'standard_work_hours_per_week' => $this->faker->optional()->numberBetween(35, 48),
            'is_current' => $end === null ? true : $this->faker->boolean(50),
            'contract_document_path' => null,
            'terms_and_conditions' => null,
        ];
    }
}
