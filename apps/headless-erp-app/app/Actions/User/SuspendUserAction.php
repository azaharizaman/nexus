<?php

declare(strict_types=1);

namespace Nexus\Erp\Actions\User;

use Nexus\Erp\Events\Auth\UserSuspendedEvent;
use Nexus\Erp\Models\User;
use Nexus\Erp\Support\Contracts\ActivityLoggerContract;
use Nexus\Erp\Support\Contracts\TokenServiceContract;
use Nexus\Erp\Enums\UserStatus;
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
    ) {
    }

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
