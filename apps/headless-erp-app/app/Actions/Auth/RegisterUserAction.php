<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Contracts\UserRepositoryContract;
use App\Events\Auth\UserRegisteredEvent;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Register User Action
 *
 * Creates a new user account with tenant scoping and proper validation.
 */
class RegisterUserAction
{
    use AsAction;

    /**
     * Create a new action instance
     */
    public function __construct(
        private readonly UserRepositoryContract $userRepository
    ) {}

    /**
     * Register a new user
     *
     * @param  array<string, mixed>  $data  User registration data
     * @return User The created user
     *
     * @throws \RuntimeException If email already exists in tenant
     */
    public function handle(array $data): User
    {
        // Create user through repository (handles password hashing and validation)
        $user = $this->userRepository->create($data);

        // Dispatch registration event
        event(new UserRegisteredEvent($user));

        return $user;
    }
}
