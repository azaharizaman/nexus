<?php

declare(strict_types=1);

namespace Nexus\Hrm\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Nexus\Hrm\Models\AttendanceRecord;

class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    public function definition(): array
    {
        $clockIn = $this->faker->dateTimeBetween('-1 month', 'now');
        $clockOut = $this->faker->boolean(80) ? (clone $clockIn)->modify('+'.random_int(6, 10).' hours') : null;

        $breakMinutes = $clockOut ? random_int(30, 90) : 0;
        $workedMinutes = $clockOut ? (strtotime($clockOut->format('Y-m-d H:i:s')) - strtotime($clockIn->format('Y-m-d H:i:s'))) / 60 - $breakMinutes : 0;
        $overtimeMinutes = $workedMinutes > 480 ? $workedMinutes - 480 : 0;

        return [
            'tenant_id' => (string) Str::ulid(),
            'employee_id' => (string) Str::ulid(),
            'date' => $clockIn->format('Y-m-d'),
            'clock_in_at' => $clockIn,
            'clock_out_at' => $clockOut,
            'break_minutes' => $breakMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'location' => $this->faker->optional()->address(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Create a complete attendance record (with clock out)
     */
    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'clock_out_at' => now()->addHours(random_int(6, 10)),
        ]);
    }

    /**
     * Create an open attendance record (no clock out)
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'clock_out_at' => null,
            'break_minutes' => 0,
            'overtime_minutes' => 0,
        ]);
    }
}
