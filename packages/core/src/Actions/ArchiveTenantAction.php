<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\Core\Actions;

use Azaharizaman\Erp\Core\Contracts\TenantRepositoryContract;
use Azaharizaman\Erp\Core\Enums\TenantStatus;
use Azaharizaman\Erp\Core\Events\TenantArchivedEvent;
use Azaharizaman\Erp\Core\Models\Tenant;
use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Archive Tenant Action
 *
 * Archives (soft deletes) a tenant with audit logging.
 */
class ArchiveTenantAction
{
    use AsAction;

    /**
     * Create a new action instance
     *
     * @param  TenantRepositoryContract  $repository  The tenant repository
     */
    public function __construct(
        protected readonly TenantRepositoryContract $repository
    ) {}

    /**
     * Handle the action
     *
     * @param  Tenant  $tenant  The tenant to archive
     * @return bool True if archived successfully
     */
    public function handle(Tenant $tenant): bool
    {
        // Update status to archived before soft delete
        $tenant->status = TenantStatus::ARCHIVED;
        $tenant->save();

        // Soft delete the tenant using repository
        $result = $this->repository->archive($tenant);

        if ($result) {
            // Dispatch event
            event(new TenantArchivedEvent($tenant));
        }

        return $result;
    }

    /**
     * Make action available as a job
     *
     * @param  Tenant  $tenant  The tenant to archive
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

        if ($tenant->isArchived()) {
            $command->warn("Tenant is already archived: {$tenant->name} ({$tenant->id})");

            return;
        }

        // Confirm before archiving
        $confirmation = $command->option('force')
            || $command->confirm("Are you sure you want to archive tenant '{$tenant->name}'?");

        if (! $confirmation) {
            $command->info('Archive operation cancelled.');

            return;
        }

        $result = $this->handle($tenant);

        if ($result) {
            $command->info("Tenant archived successfully: {$tenant->name} ({$tenant->id})");
        } else {
            $command->error("Failed to archive tenant: {$tenant->name} ({$tenant->id})");
        }
    }
}
