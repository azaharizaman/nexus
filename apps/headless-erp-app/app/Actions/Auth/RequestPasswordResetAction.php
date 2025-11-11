<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Contracts\UserRepositoryContract;
use App\Events\Auth\PasswordResetRequestedEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Request Password Reset Action
 *
 * Generates a secure password reset token and stores it in database.
 */
class RequestPasswordResetAction
{
    use AsAction;

    /**
     * Create a new action instance
     */
    public function __construct(
        private readonly UserRepositoryContract $userRepository
    ) {}

    /**
     * Request password reset for user
     *
     * @param  string  $email  User's email address
     * @param  string  $tenantId  UUID of the tenant
     * @return void
     *
     * @throws \RuntimeException If user not found
     */
    public function handle(string $email, string $tenantId): void
    {
        // Find user by email in tenant
        $user = $this->userRepository->findByEmail($email, $tenantId);

        if (! $user) {
            throw new \RuntimeException('User not found with provided email');
        }

        // Generate secure reset token (64 characters)
        $token = Str::random(64);

        // Store token with 1-hour expiration
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => hash('sha256', $token), // Hash for security
                'created_at' => now(),
            ]
        );

        // Dispatch password reset requested event
        event(new PasswordResetRequestedEvent($user, $token));
    }
}
