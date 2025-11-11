<?php

declare(strict_types=1);

namespace Database\Factories;

use Azaharizaman\Erp\Core\Enums\UserStatus;
use Azaharizaman\Erp\Core\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'status' => UserStatus::ACTIVE,
            'last_login_at' => fake()->optional(0.7)->dateTimeBetween('-30 days', 'now'),
            'mfa_enabled' => false,
            'mfa_secret' => null,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'is_admin' => false,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is an administrator.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
        ]);
    }

    /**
     * Indicate that the user has MFA enabled.
     */
    public function withMfa(): static
    {
        return $this->state(fn (array $attributes) => [
            'mfa_enabled' => true,
            'mfa_secret' => Str::random(32),
        ]);
    }

    /**
     * Indicate that the user account is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::INACTIVE,
        ]);
    }

    /**
     * Indicate that the user account is locked.
     */
    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::LOCKED,
            'failed_login_attempts' => 5,
            'locked_until' => now()->addMinutes(30),
        ]);
    }

    /**
     * Indicate that the user account is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::SUSPENDED,
        ]);
    }

    /**
     * Indicate that the user has failed login attempts.
     *
     * @param  int  $attempts  Number of failed login attempts
     */
    public function withFailedAttempts(int $attempts = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'failed_login_attempts' => $attempts,
        ]);
    }
}
