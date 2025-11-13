<?php

declare(strict_types=1);

namespace App\Console\Commands\Tenant;

use Nexus\Erp\Core\Contracts\TenantRepositoryContract;
use Nexus\Erp\Core\Enums\TenantStatus;
use Illuminate\Console\Command;

/**
 * List Tenants Command
 *
 * Lists all tenants in table format with optional filtering.
 *
 * @return int Command exit code
 */
class ListTenantsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:tenant:list
                            {--status= : Filter by status (active, suspended, archived)}
                            {--search= : Search in name or domain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all tenants with optional filtering';

    /**
     * Create a new command instance
     *
     * @param  TenantRepositoryContract  $tenantRepository  The tenant repository
     */
    public function __construct(
        private readonly TenantRepositoryContract $tenantRepository
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
        $this->info('Fetching tenants...');
        $this->newLine();

        try {
            // Build tenant query via repository
            $query = $this->tenantRepository->query();

            // Apply status filter if provided
            if ($this->option('status')) {
                $status = $this->option('status');

                // Validate status
                if (! in_array($status, TenantStatus::values(), true)) {
                    $this->error("Invalid status: {$status}");
                    $this->line('Valid statuses: ' . implode(', ', TenantStatus::values()));

                    return self::FAILURE;
                }

                $query->where('status', $status);
            }

            // Apply search filter if provided
            if ($this->option('search')) {
                $search = $this->option('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('domain', 'like', "%{$search}%");
                });
            }

            // Get tenants with ordering
            $tenants = $query->orderBy('created_at', 'desc')->get();

            if ($tenants->isEmpty()) {
                $this->warn('No tenants found.');

                return self::SUCCESS;
            }

            // Format data for table
            $tableData = $tenants->map(function ($tenant) {
                return [
                    $tenant->id,
                    $tenant->name,
                    $tenant->domain,
                    $tenant->status?->label() ?? 'Unknown',
                    $tenant->created_at->format('Y-m-d H:i:s'),
                ];
            })->toArray();

            // Display table
            $this->table(
                ['ID', 'Name', 'Domain', 'Status', 'Created At'],
                $tableData
            );

            $this->newLine();
            $this->info("Total: {$tenants->count()} tenant(s)");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->newLine();
            $this->error("✗ Failed to list tenants: {$e->getMessage()}");
            $this->newLine();

            return self::FAILURE;
        }
    }
}
