<?php

declare(strict_types=1);

namespace Nexus\Erp\Core\Actions;

use App\Support\Contracts\ActivityLoggerContract;
use Nexus\Erp\Core\Contracts\TenantRepositoryContract;
use Nexus\Erp\Core\Enums\TenantStatus;
use Nexus\Erp\Core\Events\TenantArchivedEvent;
use Nexus\Erp\Core\Models\Tenant;
use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Archive Tenant Action
 *
 * Archives a tenant with a reason and audit logging.
 */
class ArchiveTenantAction
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
     * @param  Tenant  $tenant  The tenant to archive
     * @param  string  $reason  The reason for archiving
     * @return Tenant The archived tenant
     *
     * @throws \InvalidArgumentException If tenant is already archived
     */
    public function handle(Tenant $tenant, string $reason): Tenant
    {
        // Check if tenant is not already archived
        if ($tenant->isArchived()) {
            throw new \InvalidArgumentException('Tenant is already archived');
        }

        // Update tenant status
        $this->repository->update($tenant, [
            'status' => TenantStatus::ARCHIVED,
        ]);

        // Refresh the model
        $tenant->refresh();

        // Log activity with reason
        $this->activityLogger->log(
            'Tenant archived',
            $tenant,
            auth()->check() ? auth()->user() : null,
            ['reason' => $reason]
        );

        // Dispatch event
        event(new TenantArchivedEvent($tenant));

        return $tenant;
    }

    /**
     * Make action available as a job
     *
     * @param  Tenant  $tenant  The tenant to archive
     * @param  string  $reason  The reason for archiving
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
            $archivedTenant = $this->handle($tenant, $reason);
            $command->info("Tenant archived successfully: {$archivedTenant->name} ({$archivedTenant->id})");
        } catch (\InvalidArgumentException $e) {
            $command->error($e->getMessage());
        }
    }
}
