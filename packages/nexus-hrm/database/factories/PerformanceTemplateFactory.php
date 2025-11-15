<?php

declare(strict_types=1);

namespace Nexus\Hrm\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Nexus\Hrm\Models\PerformanceTemplate;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Nexus\Hrm\Models\PerformanceTemplate>
 */
class PerformanceTemplateFactory extends Factory
{
    protected $model = PerformanceTemplate::class;

    public function definition(): array
    {
        return [
            'tenant_id' => (string) Str::ulid(),
            'name' => $this->faker->words(2, true) . ' Template',
            'description' => $this->faker->sentence(),
            'template_data' => [
                'kpis' => [
                    [
                        'name' => 'Quality of Work',
                        'description' => 'Accuracy and thoroughness of deliverables',
                        'weight' => 25,
                        'max_score' => 5,
                    ],
                    [
                        'name' => 'Productivity',
                        'description' => 'Volume and efficiency of output',
                        'weight' => 25,
                        'max_score' => 5,
                    ],
                    [
                        'name' => 'Teamwork',
                        'description' => 'Collaboration and communication skills',
                        'weight' => 20,
                        'max_score' => 5,
                    ],
                    [
                        'name' => 'Initiative',
                        'description' => 'Proactive problem-solving and innovation',
                        'weight' => 15,
                        'max_score' => 5,
                    ],
                    [
                        'name' => 'Professional Development',
                        'description' => 'Learning and skill improvement',
                        'weight' => 15,
                        'max_score' => 5,
                    ],
                ],
                'competencies' => [
                    'Technical Skills',
                    'Communication',
                    'Leadership',
                    'Problem Solving',
                    'Adaptability',
                ],
                'rating_scale' => [
                    1 => 'Needs Improvement',
                    2 => 'Below Expectations',
                    3 => 'Meets Expectations',
                    4 => 'Exceeds Expectations',
                    5 => 'Outstanding',
                ],
            ],
            'is_active' => true,
            'created_by' => (string) Str::ulid(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}