<?php

declare(strict_types=1);

namespace Nexus\Hrm\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Nexus\Hrm\Enums\EmploymentStatus;
use Nexus\Hrm\Models\Employee;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $first = $this->faker->firstName();
        $last = $this->faker->lastName();

        return [
            'tenant_id' => (string) Str::ulid(),
            'employee_number' => strtoupper($this->faker->bothify('EMP#####')),
            'first_name' => $first,
            'last_name' => $last,
            'middle_name' => null,
            'email' => strtolower($first.'.'.$last).'@example.com',
            'phone' => $this->faker->optional()->e164PhoneNumber(),
            'mobile' => $this->faker->optional()->e164PhoneNumber(),
            'date_of_birth' => $this->faker->optional()->dateTimeBetween('-60 years', '-18 years'),
            'national_id' => $this->faker->optional()->bothify('ID########'),
            'passport_number' => $this->faker->optional()->bothify('P########'),
            'gender' => $this->faker->optional()->randomElement(['male', 'female', 'other']),
            'marital_status' => $this->faker->optional()->randomElement(['single', 'married']),
            'emergency_contacts' => null,
            'dependents' => null,
            'employment_status' => EmploymentStatus::ACTIVE,
            'hire_date' => $this->faker->optional()->dateTimeBetween('-2 years', 'now'),
            'termination_date' => null,
            'termination_reason' => null,
        ];
    }
}
