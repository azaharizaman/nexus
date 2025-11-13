<?php

declare(strict_types=1);

namespace Nexus\Erp\Core\Actions;

use App\Support\Contracts\ActivityLoggerContract;
use Nexus\Erp\Core\Contracts\TenantRepositoryContract;
use Nexus\Erp\Core\Enums\TenantStatus;
use Nexus\Erp\Core\Events\TenantActivatedEvent;
use Nexus\Erp\Core\Models\Tenant;
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
     * @param  string|null  $reason  The reason for activation
     * @return Tenant The activated tenant
     *
     * @throws \InvalidArgumentException If tenant is already active
     */
    public function handle(Tenant $tenant, ?string $reason = null): Tenant
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
        $logProperties = ['previous_status' => $previousStatus->value];
        if ($reason !== null) {
            $logProperties['reason'] = $reason;
        }

        $this->activityLogger->log(
            'Tenant activated',
            $tenant,
            auth()->check() ? auth()->user() : null,
            $logProperties
        );

        // Dispatch event
        event(new TenantActivatedEvent($tenant));

        return $tenant;
    }

    /**
     * Make action available as a job
     *
     * @param  Tenant  $tenant  The tenant to activate
     * @param  string|null  $reason  The reason for activation
     */
    public function asJob(Tenant $tenant, ?string $reason = null): void
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
