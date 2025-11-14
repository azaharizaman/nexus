<?php

declare(strict_types=1);

namespace Nexus\Erp\Console\Commands\Backoffice;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Install Nexus Backoffice Command
 * 
 * Handles the initial installation of the Nexus Backoffice package.
 * 
 * This command orchestrates the setup of organizational structure management
 * including configuration publishing, migration execution, and default data creation.
 */
class InstallBackofficeCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'nexus:backoffice:install {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     */
    protected $description = 'Install the Nexus Backoffice organizational management package';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Installing Nexus Backoffice package...');

        // Publish configuration
        $this->call('vendor:publish', [
            '--provider' => 'Nexus\\Backoffice\\BackofficeServiceProvider',
            '--tag' => 'nexus-backoffice-config',
            '--force' => $this->option('force'),
        ]);

        // Publish migrations
        $this->call('vendor:publish', [
            '--provider' => 'Nexus\\Backoffice\\BackofficeServiceProvider',
            '--tag' => 'nexus-backoffice-migrations',
            '--force' => $this->option('force'),
        ]);

        // Ask if user wants to run migrations
        if ($this->confirm('Do you want to run the migrations now?', true)) {
            $this->call('migrate');
        }

        // Ask if user wants to create default office types
        if ($this->confirm('Do you want to create default office types?', true)) {
            $this->call('nexus:backoffice:create-office-types');
        }

        $this->info('Nexus Backoffice package installed successfully!');
        
        $this->comment('Next steps:');
        $this->line('1. Review the configuration file at config/backoffice.php');
        $this->line('2. Create your first company using the Company model');
        $this->line('3. Set up offices, departments, and staff as needed');
        $this->line('4. Use Laravel Actions for orchestrated operations');

        return self::SUCCESS;
    }
}