<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\Core\Actions;

use App\Models\User;
use Azaharizaman\Erp\Core\Models\Tenant;
use Azaharizaman\Erp\Core\Services\ImpersonationService;
use Illuminate\Auth\Access\AuthorizationException;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Start Impersonation Action
 *
 * Allows authorized users (admin/support) to impersonate a tenant.
 */
class StartImpersonationAction
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
     * @param  User  $user  The user starting impersonation
     * @param  Tenant  $tenant  The tenant to impersonate
     * @param  string  $reason  The reason for impersonation
     *
     * @throws AuthorizationException If user is not authorized
     * @throws \RuntimeException If user is not authenticated
     */
    public function handle(User $user, Tenant $tenant, string $reason): void
    {
        $this->impersonationService->startImpersonation($user, $tenant, $reason);
    }

    /**
     * Make action available as a job
     *
     * @param  User  $user  The user starting impersonation
     * @param  Tenant  $tenant  The tenant to impersonate
     * @param  string  $reason  The reason for impersonation
     */
    public function asJob(User $user, Tenant $tenant, string $reason): void
    {
        $this->handle($user, $tenant, $reason);
    }
}
