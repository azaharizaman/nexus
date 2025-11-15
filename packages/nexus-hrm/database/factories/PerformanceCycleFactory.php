<?php

declare(strict_types=1);

namespace Nexus\Hrm\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Nexus\Hrm\Models\PerformanceCycle;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Nexus\Hrm\Models\PerformanceCycle>
 */
class PerformanceCycleFactory extends Factory
{
    protected $model = PerformanceCycle::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+6 months');
        $endDate = $this->faker->dateTimeBetween($startDate, '+12 months');

        return [
            'tenant_id' => (string) Str::ulid(),
            'name' => $this->faker->words(3, true) . ' Review Cycle',
            'description' => $this->faker->sentence(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'frequency' => $this->faker->randomElement(['annual', 'bi-annual', 'quarterly', 'monthly']),
            'status' => $this->faker->randomElement(['draft', 'active', 'completed']),
            'auto_schedule_reviews' => $this->faker->boolean(30),
            'review_deadline_days' => $this->faker->numberBetween(14, 60),
            'reminder_days_before' => $this->faker->numberBetween(3, 14),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function annual(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'annual',
        ]);
    }
}