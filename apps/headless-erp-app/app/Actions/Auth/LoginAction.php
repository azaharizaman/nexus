<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Contracts\UserRepositoryContract;
use App\Events\Auth\UserLoggedInEvent;
use App\Events\Auth\LoginFailedEvent;
use App\Exceptions\AccountLockedException;
use App\Models\User;
use Azaharizaman\Erp\Core\Enums\UserStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Login Action
 *
 * Authenticates a user and generates an API token.
 * Handles account lockout after failed attempts and tenant scoping.
 */
class LoginAction
{
    use AsAction;

    /**
     * Create a new action instance
     */
    public function __construct(
        private readonly UserRepositoryContract $userRepository
    ) {}

    /**
     * Authenticate user and generate API token
     *
     * @param  string  $email  User's email address
     * @param  string  $password  User's password
     * @param  string  $deviceName  Name of the device requesting access
     * @param  string  $tenantId  UUID of the tenant
     * @return array{token: string, user: User, expires_at: Carbon}
     *
     * @throws AccountLockedException If account is locked
     * @throws ValidationException If credentials are invalid
     */
    public function handle(string $email, string $password, string $deviceName, string $tenantId): array
    {
        // Find user by email within tenant
        $user = $this->userRepository->findByEmail($email, $tenantId);

        // Check if user exists
        if (! $user) {
            $this->handleFailedLogin($email, $tenantId);
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if account is locked (permanent or temporary)
        if ($user->isLocked()) {
            throw new AccountLockedException(
                'Account is locked. '.($user->locked_until ? 'Try again after '.$user->locked_until->format('Y-m-d H:i:s') : 'Please contact administrator.')
            );
        }

        // Check if account is active
        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['Your account is inactive. Please contact administrator.'],
            ]);
        }

        // Verify password
        if (! Hash::check($password, $user->password)) {
            $user->incrementFailedLoginAttempts();
            $this->handleFailedLogin($email, $tenantId, $user);
            
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Reset failed login attempts on successful login
        $user->resetFailedLoginAttempts();
        $user->updateLastLogin();

        // Generate API token with configurable expiration
        $expiresAt = now()->addDays(config('authentication.token_expiration_days', 30));
        $token = $user->createApiToken($deviceName, $expiresAt);

        // Dispatch login success event
        event(new UserLoggedInEvent($user, $token->plainTextToken, $deviceName));

        return [
            'token' => $token->plainTextToken,
            'user' => $user,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Handle failed login attempt
     *
     * @param  string  $email  User's email
     * @param  string  $tenantId  Tenant ID
     * @param  User|null  $user  User if found
     * @return void
     */
    private function handleFailedLogin(string $email, string $tenantId, ?User $user = null): void
    {
        $attemptsRemaining = $user ? (5 - $user->failed_login_attempts) : 0;

        event(new LoginFailedEvent($email, $tenantId, $attemptsRemaining));
    }
}
