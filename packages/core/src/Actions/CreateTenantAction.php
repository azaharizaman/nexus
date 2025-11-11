<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\Core\Actions;

use Azaharizaman\Erp\Core\Contracts\TenantRepositoryContract;
use Azaharizaman\Erp\Core\Enums\TenantStatus;
use Azaharizaman\Erp\Core\Events\TenantCreatedEvent;
use Azaharizaman\Erp\Core\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Create Tenant Action
 *
 * Creates a new tenant with validation and audit logging.
 */
class CreateTenantAction
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
     * @param  array<string, mixed>  $data  Tenant data
     * @return Tenant The created tenant
     *
     * @throws ValidationException If validation fails
     */
    public function handle(array $data): Tenant
    {
        // Validate input data
        $validatedData = $this->validate($data);

        // Set default status if not provided
        $validatedData['status'] = $validatedData['status'] ?? TenantStatus::ACTIVE;

        // Create tenant using repository
        $tenant = $this->repository->create($validatedData);

        // Dispatch event
        event(new TenantCreatedEvent($tenant));

        return $tenant;
    }

    /**
     * Validate the input data
     *
     * @param  array<string, mixed>  $data  The data to validate
     * @return array<string, mixed> Validated data
     *
     * @throws ValidationException If validation fails
     */
    protected function validate(array $data): array
    {
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255', 'unique:tenants,domain'],
            'status' => ['nullable', 'string', Rule::in(TenantStatus::values())],
            'configuration' => ['nullable', 'array'],
            'subscription_plan' => ['nullable', 'string', 'max:255'],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Make action available as a job
     *
     * @param  array<string, mixed>  $data  Tenant data
     */
    public function asJob(array $data): void
    {
        $this->handle($data);
    }

    /**
     * Make action available as a CLI command
     *
     * @param  Command  $command  The console command
     */
    public function asCommand(Command $command): void
    {
        $data = [
            'name' => $command->argument('name') ?? $command->ask('Tenant name'),
            'domain' => $command->argument('domain') ?? $command->ask('Tenant domain'),
            'contact_email' => $command->argument('email') ?? $command->ask('Contact email'),
            'contact_name' => $command->option('contact_name'),
            'contact_phone' => $command->option('contact_phone'),
            'billing_email' => $command->option('billing_email'),
            'subscription_plan' => $command->option('subscription_plan'),
        ];

        try {
            $tenant = $this->handle($data);
            $command->info("Tenant created successfully: {$tenant->name} ({$tenant->id})");
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
