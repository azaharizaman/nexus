<?php

declare(strict_types=1);

namespace Nexus\Erp\Core\Actions;

use Nexus\Erp\Core\Contracts\TenantRepositoryContract;
use Nexus\Erp\Core\Enums\TenantStatus;
use Nexus\Erp\Core\Events\TenantUpdatedEvent;
use Nexus\Erp\Core\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Update Tenant Action
 *
 * Updates an existing tenant with validation and audit logging.
 */
class UpdateTenantAction
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
     * @param  Tenant  $tenant  The tenant to update
     * @param  array<string, mixed>  $data  Updated data
     * @return Tenant The updated tenant
     *
     * @throws ValidationException If validation fails
     */
    public function handle(Tenant $tenant, array $data): Tenant
    {
        // Store original data for event
        $originalData = $tenant->only([
            'name',
            'domain',
            'status',
            'configuration',
            'subscription_plan',
            'billing_email',
            'contact_name',
            'contact_email',
            'contact_phone',
        ]);

        // Validate input data
        $validatedData = $this->validate($tenant, $data);

        // Update tenant using repository
        $this->repository->update($tenant, $validatedData);

        // Clear tenant cache
        $this->clearTenantCache($tenant);

        // Refresh the model to get updated values
        $tenant->refresh();

        // Dispatch event
        event(new TenantUpdatedEvent($tenant, $originalData));

        return $tenant;
    }

    /**
     * Clear all caches related to the tenant
     *
     * @param  Tenant  $tenant  The tenant
     */
    protected function clearTenantCache(Tenant $tenant): void
    {
        // Clear tenant-specific cache keys
        \Illuminate\Support\Facades\Cache::forget("tenant:{$tenant->id}");
        \Illuminate\Support\Facades\Cache::forget("tenant:domain:{$tenant->domain}");

        // Clear any other tenant-related cache tags if using cache tagging
        if (config('cache.default') === 'redis') {
            \Illuminate\Support\Facades\Cache::tags(['tenants', "tenant:{$tenant->id}"])->flush();
        }
    }

    /**
     * Validate the input data
     *
     * @param  Tenant  $tenant  The tenant being updated
     * @param  array<string, mixed>  $data  The data to validate
     * @return array<string, mixed> Validated data
     *
     * @throws ValidationException If validation fails
     */
    protected function validate(Tenant $tenant, array $data): array
    {
        $validator = Validator::make($data, [
            'name' => ['sometimes', 'string', 'max:255'],
            'domain' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('tenants', 'domain')->ignore($tenant->id),
            ],
            'status' => ['sometimes', 'string', Rule::in(TenantStatus::values())],
            'configuration' => ['sometimes', 'array'],
            'subscription_plan' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billing_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'contact_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contact_email' => ['sometimes', 'email', 'max:255'],
            'contact_phone' => ['sometimes', 'nullable', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Make action available as a job
     *
     * @param  Tenant  $tenant  The tenant to update
     * @param  array<string, mixed>  $data  Updated data
     */
    public function asJob(Tenant $tenant, array $data): void
    {
        $this->handle($tenant, $data);
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

        $data = array_filter([
            'name' => $command->option('name'),
            'domain' => $command->option('domain'),
            'status' => $command->option('status'),
            'contact_email' => $command->option('contact_email'),
            'contact_name' => $command->option('contact_name'),
            'contact_phone' => $command->option('contact_phone'),
            'billing_email' => $command->option('billing_email'),
            'subscription_plan' => $command->option('subscription_plan'),
        ], fn ($value) => $value !== null);

        if (empty($data)) {
            $command->error('No update data provided. Use options like --name, --domain, etc.');

            return;
        }

        try {
            $updatedTenant = $this->handle($tenant, $data);
            $command->info("Tenant updated successfully: {$updatedTenant->name} ({$updatedTenant->id})");
        } catch (ValidationException $e) {
            $command->error('Validation failed:');
            foreach ($e->errors() as $field => $errors) {
                foreach ($errors as $error) {
                    $command->error("  - {$field}: {$error}");
                }
            }
            $command->line('');
        }
    }
}
