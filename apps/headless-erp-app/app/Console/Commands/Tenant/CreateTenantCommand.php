<?php

declare(strict_types=1);

namespace App\Console\Commands\Tenant;

use Azaharizaman\Erp\Core\Actions\CreateTenantAction;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

/**
 * Create Tenant Command
 *
 * Creates a new tenant with interactive prompts or command options.
 *
 * @return int Command exit code
 */
class CreateTenantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:tenant:create
                            {--name= : Tenant name}
                            {--domain= : Tenant domain}
                            {--email= : Contact email address}
                            {--contact-name= : Contact person name}
                            {--contact-phone= : Contact phone number}
                            {--billing-email= : Billing email address}
                            {--subscription-plan= : Subscription plan name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new tenant with interactive prompts or command options';

    /**
     * Create a new command instance
     *
     * @param  CreateTenantAction  $createTenantAction  The action to create tenants
     */
    public function __construct(
        private readonly CreateTenantAction $createTenantAction
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command
     *
     * @return int Command exit code (0 for success, 1 for failure)
     */
    public function handle(): int
    {
        $this->info('Creating a new tenant...');
        $this->newLine();

        // Gather required data from options or interactive prompts
        $name = $this->option('name') ?? $this->ask('Tenant name');
        $domain = $this->option('domain') ?? $this->ask('Tenant domain');
        $email = $this->option('email') ?? $this->ask('Contact email');

        // Build data array with required fields
        $data = [
            'name' => $name,
            'domain' => $domain,
            'contact_email' => $email,
        ];

        // Add optional fields if provided via options
        if ($this->option('contact-name')) {
            $data['contact_name'] = $this->option('contact-name');
        }

        if ($this->option('contact-phone')) {
            $data['contact_phone'] = $this->option('contact-phone');
        }

        if ($this->option('billing-email')) {
            $data['billing_email'] = $this->option('billing-email');
        }

        if ($this->option('subscription-plan')) {
            $data['subscription_plan'] = $this->option('subscription-plan');
        }

        try {
            // Create the tenant
            $tenant = $this->createTenantAction->handle($data);

            $this->newLine();
            $this->info('✓ Tenant created successfully!');
            $this->newLine();

            // Display tenant details
            $this->table(
                ['Property', 'Value'],
                [
                    ['ID', $tenant->id],
                    ['Name', $tenant->name],
                    ['Domain', $tenant->domain],
                    ['Status', $tenant->status->label()],
                    ['Contact Email', $tenant->contact_email],
                    ['Created At', $tenant->created_at->format('Y-m-d H:i:s')],
                ]
            );

            return self::SUCCESS;
        } catch (ValidationException $e) {
            $this->newLine();
            $this->error('✗ Validation failed:');
            $this->newLine();

            foreach ($e->errors() as $field => $errors) {
                foreach ($errors as $error) {
                    $this->error("  • {$field}: {$error}");
                }
            }

            $this->newLine();

            return self::FAILURE;
        } catch (\Exception $e) {
            $this->newLine();
            $this->error("✗ Failed to create tenant: {$e->getMessage()}");
            $this->newLine();

            return self::FAILURE;
        }
    }
}
