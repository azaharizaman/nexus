<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\Core\Actions;

use Azaharizaman\Erp\Core\Contracts\TenantRepositoryContract;
use Azaharizaman\Erp\Core\Events\TenantDeletedEvent;
use Azaharizaman\Erp\Core\Models\Tenant;
use App\Support\Contracts\ActivityLoggerContract;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Delete Tenant Action
 *
 * Soft-deletes a tenant, clears caches, and logs the action.
 */
class DeleteTenantAction
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
     * @param  Tenant  $tenant  The tenant to delete
     * @return bool True if deletion was successful
     */
    public function handle(Tenant $tenant): bool
    {
        // Log activity before deletion (while tenant still exists)
        $this->activityLogger->log(
            'Tenant deleted',
            $tenant,
            auth()->check() ? auth()->user() : null,
            [
                'tenant_name' => $tenant->name,
                'tenant_domain' => $tenant->domain,
            ]
        );

        // Soft delete the tenant
        $deleted = $this->repository->delete($tenant);

        // Clear all tenant-related caches
        $this->clearTenantCaches($tenant);

        // Dispatch event
        event(new TenantDeletedEvent($tenant));

        return $deleted;
    }

    /**
     * Clear all caches related to the tenant
     *
     * @param  Tenant  $tenant  The tenant
     */
    protected function clearTenantCaches(Tenant $tenant): void
    {
        // Clear tenant-specific cache keys
        Cache::forget("tenant:{$tenant->id}");
        Cache::forget("tenant:domain:{$tenant->domain}");
        
        // Clear any other tenant-related cache tags if using cache tagging
        if (config('cache.default') === 'redis') {
            Cache::tags(['tenants', "tenant:{$tenant->id}"])->flush();
        }
    }

    /**
     * Make action available as a job
     *
     * @param  Tenant  $tenant  The tenant to delete
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

        // Confirm deletion
        if (! $command->confirm("Are you sure you want to delete tenant '{$tenant->name}'?")) {
            $command->info('Deletion cancelled.');

            return;
        }

        $success = $this->handle($tenant);

        if ($success) {
            $command->info("Tenant deleted successfully: {$tenant->name} ({$tenant->id})");
        } else {
            $command->error('Failed to delete tenant');
        }
    }
}
