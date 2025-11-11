<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\Core\Actions;

use App\Models\User;
use Azaharizaman\Erp\Core\Services\ImpersonationService;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * End Impersonation Action
 *
 * Ends the current impersonation session and restores original tenant context.
 */
class EndImpersonationAction
{
    use AsAction;

    /**
     * Create a new action instance
     */
    public function __construct(
        protected readonly ImpersonationService $impersonationService
    ) {}

    /**
     * Handle the action
     *
     * @param  User  $user  The user ending impersonation
     */
    public function handle(User $user): void
    {
        $this->impersonationService->endImpersonation($user);
    }

    /**
     * Make action available as a job
     *
     * @param  User  $user  The user ending impersonation
     */
    public function asJob(User $user): void
    {
        $this->handle($user);
    }
}
