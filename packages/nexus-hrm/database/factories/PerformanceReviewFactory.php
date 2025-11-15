<?php

declare(strict_types=1);

namespace Nexus\Hrm\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Nexus\Hrm\Models\PerformanceReview;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Nexus\Hrm\Models\PerformanceReview>
 */
class PerformanceReviewFactory extends Factory
{
    protected $model = PerformanceReview::class;

    public function definition(): array
    {
        $isCompleted = $this->faker->boolean(70);

        $scores = $isCompleted ? [
            [
                'kpi' => 'Quality of Work',
                'score' => $this->faker->numberBetween(1, 5),
                'weight' => 25,
                'comments' => $this->faker->sentence(),
            ],
            [
                'kpi' => 'Productivity',
                'score' => $this->faker->numberBetween(1, 5),
                'weight' => 25,
                'comments' => $this->faker->sentence(),
            ],
            [
                'kpi' => 'Teamwork',
                'score' => $this->faker->numberBetween(1, 5),
                'weight' => 20,
                'comments' => $this->faker->sentence(),
            ],
            [
                'kpi' => 'Initiative',
                'score' => $this->faker->numberBetween(1, 5),
                'weight' => 15,
                'comments' => $this->faker->sentence(),
            ],
            [
                'kpi' => 'Professional Development',
                'score' => $this->faker->numberBetween(1, 5),
                'weight' => 15,
                'comments' => $this->faker->sentence(),
            ],
        ] : null;

        $overallRating = $isCompleted ? $this->calculateOverallRating($scores) : null;

        return [
            'tenant_id' => (string) Str::ulid(),
            'employee_id' => (string) Str::ulid(),
            'reviewer_id' => (string) Str::ulid(),
            'performance_cycle_id' => (string) Str::ulid(),
            'review_template_id' => (string) Str::ulid(),
            'review_date' => $this->faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'overall_rating' => $overallRating,
            'reviewer_comments' => $isCompleted ? $this->faker->paragraph() : null,
            'employee_comments' => $isCompleted && $this->faker->boolean(50) ? $this->faker->paragraph() : null,
            'status' => $isCompleted ? 'completed' : $this->faker->randomElement(['draft', 'pending', 'in_progress']),
            'scores' => $scores,
            'goals_assessment' => $isCompleted ? [
                [
                    'goal' => $this->faker->sentence(),
                    'progress' => $this->faker->numberBetween(0, 100),
                    'assessment' => $this->faker->sentence(),
                ],
            ] : null,
            'development_plan' => $isCompleted ? [
                [
                    'area' => $this->faker->word(),
                    'action' => $this->faker->sentence(),
                    'timeline' => $this->faker->words(2, true),
                ],
            ] : null,
            'next_review_date' => $isCompleted ? $this->faker->dateTimeBetween('now', '+6 months')->format('Y-m-d') : null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'overall_rating' => $this->faker->randomFloat(2, 1, 5),
            'reviewer_comments' => $this->faker->paragraph(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    private function calculateOverallRating(?array $scores): ?float
    {
        if (!$scores) {
            return null;
        }

        $totalWeightedScore = 0;
        $totalWeight = 0;

        foreach ($scores as $score) {
            $weight = $score['weight'] ?? 1;
            $totalWeightedScore += $score['score'] * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? round($totalWeightedScore / $totalWeight, 2) : null;
    }
}