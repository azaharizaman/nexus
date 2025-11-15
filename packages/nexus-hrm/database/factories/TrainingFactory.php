<?php

declare(strict_types=1);

namespace Nexus\Hrm\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Nexus\Hrm\Models\Training;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Nexus\Hrm\Models\Training>
 */
class TrainingFactory extends Factory
{
    protected $model = Training::class;

    public function definition(): array
    {
        return [
            'tenant_id' => (string) Str::ulid(),
            'title' => $this->faker->words(3, true) . ' Training',
            'description' => $this->faker->paragraph(),
            'category' => $this->faker->randomElement(['Technical', 'Leadership', 'Compliance', 'Safety', 'Soft Skills', 'Process']),
            'training_type' => $this->faker->randomElement(['internal', 'external', 'online', 'classroom', 'workshop']),
            'duration_hours' => $this->faker->randomFloat(1, 1, 40),
            'provider' => $this->faker->optional(0.7)->company(),
            'cost' => $this->faker->randomFloat(2, 0, 5000),
            'max_participants' => $this->faker->optional(0.6)->numberBetween(5, 50),
            'prerequisites' => $this->faker->optional(0.4)->randomElements([
                'Basic computer skills',
                'Previous experience required',
                'Manager approval needed',
                'Complete orientation first',
            ], 2),
            'objectives' => $this->faker->randomElements([
                'Understand core concepts',
                'Apply skills in workplace',
                'Improve performance metrics',
                'Comply with regulations',
                'Develop leadership abilities',
            ], $this->faker->numberBetween(2, 4)),
            'materials' => $this->faker->optional(0.5)->randomElements([
                ['name' => 'Training Manual', 'type' => 'pdf'],
                ['name' => 'Video Presentation', 'type' => 'mp4'],
                ['name' => 'Exercises Workbook', 'type' => 'docx'],
            ], 2),
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

    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'training_type' => 'online',
            'max_participants' => null, // Unlimited for online
        ]);
    }

    public function expensive(): static
    {
        return $this->state(fn (array $attributes) => [
            'cost' => $this->faker->randomFloat(2, 1000, 10000),
        ]);
    }
}