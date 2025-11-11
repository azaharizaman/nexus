<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\Core\Actions;

use App\Support\Contracts\ActivityLoggerContract;
use Azaharizaman\Erp\Core\Contracts\TenantRepositoryContract;
use Azaharizaman\Erp\Core\Enums\TenantStatus;
use Azaharizaman\Erp\Core\Events\TenantActivatedEvent;
use Azaharizaman\Erp\Core\Models\Tenant;
use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Activate Tenant Action
 *
 * Activates a suspended or archived tenant with audit logging.
 */
class ActivateTenantAction
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
     * @param  Tenant  $tenant  The tenant to activate
     * @return Tenant The activated tenant
     *
     * @throws \InvalidArgumentException If tenant is already active
     */
    public function handle(Tenant $tenant): Tenant
    {
        // Check if tenant is not already active
        if ($tenant->isActive()) {
            throw new \InvalidArgumentException('Tenant is already active');
        }

        // Store previous status for logging
        $previousStatus = $tenant->status;

        // Update tenant status
        $this->repository->update($tenant, [
            'status' => TenantStatus::ACTIVE,
        ]);

        // Refresh the model
        $tenant->refresh();

        // Log activity
        $this->activityLogger->log(
            'Tenant activated',
            $tenant,
            auth()->check() ? auth()->user() : null,
            ['previous_status' => $previousStatus->value]
        );

        // Dispatch event
        event(new TenantActivatedEvent($tenant));

        return $tenant;
    }

    /**
     * Make action available as a job
     *
     * @param  Tenant  $tenant  The tenant to activate
     */
    public function asJob(Tenant $tenant): void
    {
        $this->handle($tenant);
    }

    /**
     * Make action available as a CLI command
     *
     * @param  Command  $command  The console command
     */
    public function asCommand(Command $command): void
    {
        $tenantId = (string) $command->argument('tenant_id');

        $tenant = $this->repository->findById($tenantId);

        if (! $tenant) {
            $command->error("Tenant not found: {$tenantId}");

            return;
        }

        try {
            $activatedTenant = $this->handle($tenant);
            $command->info("Tenant activated successfully: {$activatedTenant->name} ({$activatedTenant->id})");
        } catch (\InvalidArgumentException $e) {
            $command->error($e->getMessage());
        }
    }
}
