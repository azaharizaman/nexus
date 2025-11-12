<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Events\Auth\UserLoggedOutEvent;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Logout Action
 *
 * Revokes API token(s) for the authenticated user.
 */
class LogoutAction
{
    use AsAction;

    /**
     * Revoke user's API token
     *
     * @param  User  $user  The authenticated user
     * @param  int|null  $tokenId  Specific token ID to revoke (null for current token)
     */
    public function handle(User $user, ?int $tokenId = null): void
    {
        if ($tokenId) {
            // Revoke specific token
            $user->revokeApiToken($tokenId);
        } else {
            // Revoke current token
            $currentToken = $user->currentAccessToken();
            if ($currentToken) {
                $currentToken->delete();
                $tokenId = $currentToken->id;
            }
        }

        // Dispatch logout event
        event(new UserLoggedOutEvent($user, $tokenId));
    }
}
