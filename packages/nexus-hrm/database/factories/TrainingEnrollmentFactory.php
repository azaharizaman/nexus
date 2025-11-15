<?php

declare(strict_types=1);

namespace Nexus\Hrm\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Nexus\Hrm\Models\TrainingEnrollment;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Nexus\Hrm\Models\TrainingEnrollment>
 */
class TrainingEnrollmentFactory extends Factory
{
    protected $model = TrainingEnrollment::class;

    public function definition(): array
    {
        $enrolledAt = $this->faker->dateTimeBetween('-6 months', 'now');
        $isCompleted = $this->faker->boolean(70);

        $scheduledDate = $isCompleted ? $this->faker->dateTimeBetween($enrolledAt, '-1 month') : $this->faker->optional()->dateTimeBetween('now', '+3 months');
        $completionDate = $isCompleted ? $this->faker->dateTimeBetween($scheduledDate ?? $enrolledAt, 'now') : null;

        $certificateIssued = $isCompleted && $this->faker->boolean(80);
        $certificateExpiry = $certificateIssued ? $this->faker->dateTimeBetween($completionDate ?? now(), '+2 years') : null;

        return [
            'tenant_id' => (string) Str::ulid(),
            'employee_id' => (string) Str::ulid(),
            'training_id' => (string) Str::ulid(),
            'enrolled_at' => $enrolledAt,
            'scheduled_date' => $scheduledDate?->format('Y-m-d'),
            'completion_date' => $completionDate?->format('Y-m-d'),
            'status' => $isCompleted ? 'completed' : $this->faker->randomElement(['enrolled', 'scheduled', 'cancelled']),
            'score' => $isCompleted ? $this->faker->randomFloat(1, 0, 100) : null,
            'feedback' => $isCompleted ? $this->faker->optional(0.7)->paragraph() : null,
            'certificate_issued' => $certificateIssued,
            'certificate_number' => $certificateIssued ? 'CERT-' . $this->faker->unique()->numberBetween(10000, 99999) : null,
            'certificate_expiry' => $certificateExpiry?->format('Y-m-d'),
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $completionDate = $this->faker->dateTimeBetween('-3 months', 'now');
            return [
                'status' => 'completed',
                'completion_date' => $completionDate->format('Y-m-d'),
                'score' => $this->faker->randomFloat(1, 60, 100),
                'certificate_issued' => $this->faker->boolean(80),
            ];
        });
    }

    public function withCertificate(): static
    {
        return $this->state(fn (array $attributes) => [
            'certificate_issued' => true,
            'certificate_number' => 'CERT-' . $this->faker->unique()->numberBetween(10000, 99999),
            'certificate_expiry' => $this->faker->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
        ]);
    }

    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'certificate_expiry' => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
        ]);
    }
}