<?php

declare(strict_types=1);

namespace Nexus\Hrm\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Nexus\Hrm\Models\DisciplinaryCase;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Nexus\Hrm\Models\DisciplinaryCase>
 */
class DisciplinaryCaseFactory extends Factory
{
    protected $model = DisciplinaryCase::class;

    public function definition(): array
    {
        $incidentDate = $this->faker->dateTimeBetween('-6 months', 'now');
        $reportedDate = $this->faker->dateTimeBetween($incidentDate, '+7 days');

        return [
            'tenant_id' => (string) Str::ulid(),
            'employee_id' => (string) Str::ulid(),
            'case_type' => $this->faker->randomElement(['verbal_warning', 'written_warning', 'performance_improvement', 'suspension', 'termination']),
            'severity' => $this->faker->randomElement(['minor', 'moderate', 'major', 'critical']),
            'description' => $this->faker->paragraph(),
            'incident_date' => $incidentDate->format('Y-m-d'),
            'reported_date' => $reportedDate->format('Y-m-d'),
            'status' => $this->faker->randomElement(['investigating', 'pending_resolution', 'resolved', 'dismissed']),
            'handler_id' => (string) Str::ulid(),
            'resolution' => $this->faker->optional(0.7)->paragraph(),
            'resolution_date' => $this->faker->optional(0.7)->dateTimeBetween($reportedDate, 'now')->format('Y-m-d'),
            'follow_up_required' => $this->faker->boolean(30),
            'follow_up_date' => $this->faker->optional()->dateTimeBetween('now', '+6 months')->format('Y-m-d'),
            'documents' => $this->faker->optional(0.5)->randomElements([
                ['name' => 'incident_report.pdf', 'url' => 'https://example.com/docs/incident.pdf'],
                ['name' => 'witness_statement.pdf', 'url' => 'https://example.com/docs/witness.pdf'],
            ], 2),
            'witnesses' => $this->faker->optional(0.6)->randomElements([
                ['name' => 'John Doe', 'position' => 'Manager', 'statement' => 'I witnessed the incident'],
                ['name' => 'Jane Smith', 'position' => 'Colleague', 'statement' => 'I was present during the event'],
            ], 2),
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved',
            'resolution' => $this->faker->paragraph(),
            'resolution_date' => $this->faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
        ]);
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'critical',
            'case_type' => 'termination',
        ]);
    }
}