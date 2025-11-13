<?php

declare(strict_types=1);

namespace Database\Factories;

use Nexus\Erp\Core\Enums\TenantStatus;
use Nexus\Erp\Core\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Nexus\Erp\Core\Models\Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $companyName = fake()->company();

        return [
            'name' => $companyName,
            'domain' => fake()->unique()->domainName(),
            'status' => TenantStatus::ACTIVE,
            'configuration' => [
                'timezone' => fake()->timezone(),
                'currency' => fake()->currencyCode(),
                'locale' => 'en',
            ],
            'subscription_plan' => fake()->randomElement(['basic', 'professional', 'enterprise']),
            'billing_email' => fake()->companyEmail(),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->companyEmail(),
            'contact_phone' => fake()->phoneNumber(),
        ];
    }

    /**
     * Indicate that the tenant is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TenantStatus::SUSPENDED,
        ]);
    }

    /**
     * Indicate that the tenant is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TenantStatus::ARCHIVED,
        ]);
    }
}
