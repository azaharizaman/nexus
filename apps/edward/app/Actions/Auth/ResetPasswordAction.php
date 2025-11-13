<?php

declare(strict_types=1);

namespace Edward\Actions\Auth;

use Nexus\Erp\Support\Contracts\UserRepositoryContract;
use Nexus\Erp\Events\Auth\PasswordResetEvent;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Reset Password Action
 *
 * Resets user password using a secure one-time token.
 */
class ResetPasswordAction
{
    use AsAction;

    /**
     * Create a new action instance
     */
    public function __construct(
        private readonly UserRepositoryContract $userRepository
    ) {
    }

    /**
     * Reset user password with token
     *
     * @param  string  $email  User's email address
     * @param  string  $token  Password reset token
     * @param  string  $newPassword  New password
     * @param  string  $tenantId  Tenant ID for tenant-scoped user lookup
     * @return bool True if password reset successful
     *
     * @throws \RuntimeException If token is invalid or expired
     */
    public function handle(string $email, string $token, string $newPassword, string $tenantId): bool
    {
        // Find token record
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (! $resetRecord) {
            throw new \RuntimeException('Invalid or expired reset token');
        }

        // Verify token (comparing hashed version)
        if (! hash_equals($resetRecord->token, hash('sha256', $token))) {
            throw new \RuntimeException('Invalid or expired reset token');
        }

        // Check if token is expired (1 hour)
        $createdAt = \Carbon\Carbon::parse($resetRecord->created_at);
        if ($createdAt->addHour()->isPast()) {
            // Delete expired token
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            throw new \RuntimeException('Reset token has expired');
        }

        // Find user using tenant-scoped repository method
        $userModel = $this->userRepository->findByEmail($email, $tenantId);

        if (! $userModel) {
            throw new \RuntimeException('User not found');
        }

        // Update password
        $this->userRepository->update($userModel, [
            'password' => $newPassword,
        ]);

        // Delete used token (single-use)
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Reset failed login attempts
        $userModel->resetFailedLoginAttempts();

        // Dispatch password reset event
        event(new PasswordResetEvent($userModel));

        return true;
    }
}
