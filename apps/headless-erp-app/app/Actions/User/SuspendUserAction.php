<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Events\Auth\UserSuspendedEvent;
use App\Models\User;
use App\Support\Contracts\ActivityLoggerContract;
use App\Support\Contracts\TokenServiceContract;
use Azaharizaman\Erp\Core\Enums\UserStatus;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Suspend User Action
 *
 * Suspends a user account, revokes all tokens, and logs the activity.
 */
class SuspendUserAction
{
    use AsAction;

    /**
     * Create a new action instance
     */
    public function __construct(
        private readonly TokenServiceContract $tokenService,
        private readonly ActivityLoggerContract $activityLogger
    ) {}

    /**
     * Execute the action
     *
     * @param  User  $user  The user to suspend
     * @param  string  $reason  The reason for suspension
     * @return User The suspended user
     */
    public function handle(User $user, string $reason): User
    {
        // Set user status to suspended
        $user->status = UserStatus::SUSPENDED;
        $user->save();

        // Revoke all tokens
        $this->tokenService->revokeAllTokens($user);

        // Log activity
        if (auth()->check()) {
            $this->activityLogger->log(
                'User suspended',
                $user,
                auth()->user(),
                ['reason' => $reason]
            );
        }

        // Dispatch event
        event(new UserSuspendedEvent($user, $reason));

        return $user;
    }
}
