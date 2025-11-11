<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\Core\Actions;

use App\Support\Contracts\ActivityLoggerContract;
use Azaharizaman\Erp\Core\Contracts\TenantRepositoryContract;
use Azaharizaman\Erp\Core\Enums\TenantStatus;
use Azaharizaman\Erp\Core\Events\TenantSuspendedEvent;
use Azaharizaman\Erp\Core\Models\Tenant;
use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Suspend Tenant Action
 *
 * Suspends an active tenant with a reason and audit logging.
 */
class SuspendTenantAction
{
    use AsAction;

    /**
     * Create a new action instance
     *
     * @param  TenantRepositoryContract  $repository  The tenant repository
     * @param  ActivityLoggerContract  $activityLogger  The activity logger
     */
    public function __construct(
        protected readonly TenantRepositoryContract $repository,
        protected readonly ActivityLoggerContract $activityLogger
    ) {}

    /**
     * Handle the action
     *
     * @param  Tenant  $tenant  The tenant to suspend
     * @param  string  $reason  The reason for suspension
     * @return Tenant The suspended tenant
     *
     * @throws \InvalidArgumentException If tenant is not active
     */
    public function handle(Tenant $tenant, string $reason): Tenant
    {
        // Check if tenant is active
        if (! $tenant->isActive()) {
            throw new \InvalidArgumentException('Only active tenants can be suspended');
        }

        // Update tenant status
        $this->repository->update($tenant, [
            'status' => TenantStatus::SUSPENDED,
        ]);

        // Refresh the model
        $tenant->refresh();

        // Log activity with reason
        $this->activityLogger->log(
            'Tenant suspended',
            $tenant,
            auth()->check() ? auth()->user() : null,
            ['reason' => $reason]
        );

        // Dispatch event
        event(new TenantSuspendedEvent($tenant, $reason));

        return $tenant;
    }

    /**
     * Make action available as a job
     *
     * @param  Tenant  $tenant  The tenant to suspend
     * @param  string  $reason  The reason for suspension
     */
    public function asJob(Tenant $tenant, string $reason): void
    {
        $this->handle($tenant, $reason);
    }

    /**
     * Make action available as a CLI command
     *
     * @param  Command  $command  The console command
     */
    public function asCommand(Command $command): void
    {
        $tenantId = (string) $command->argument('tenant_id');
        $reason = (string) $command->argument('reason');

        $tenant = $this->repository->findById($tenantId);

        if (! $tenant) {
            $command->error("Tenant not found: {$tenantId}");

            return;
        }

        try {
            $suspendedTenant = $this->handle($tenant, $reason);
            $command->info("Tenant suspended successfully: {$suspendedTenant->name} ({$suspendedTenant->id})");
        } catch (\InvalidArgumentException $e) {
            $command->error($e->getMessage());
        }
    }
}
