<?php

declare(strict_types=1);

namespace Nexus\Erp\Console\Commands\Backoffice;

use Nexus\Backoffice\Models\Staff;
use Illuminate\Console\Command;

/**
 * Process Scheduled Resignations Command
 * 
 * Orchestrates the processing of staff resignations that are scheduled for today or earlier.
 * This command should be run daily via cron to automatically update staff status.
 * 
 * This orchestration command coordinates with the Nexus Backoffice package to handle
 * the business logic of staff resignation processing while providing command line interface.
 */
class ProcessResignationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'nexus:backoffice:process-resignations
                           {--dry-run : Run without making changes}
                           {--force : Process without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Process scheduled staff resignations that are due';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Processing scheduled resignations...');

        // Get staff with pending resignations that are due
        $dueResignations = Staff::pendingResignation()
            ->whereDate('resignation_date', '<=', now()->toDateString())
            ->get();

        if ($dueResignations->isEmpty()) {
            $this->info('No resignations to process.');
            return self::SUCCESS;
        }

        $this->info("Found {$dueResignations->count()} resignation(s) to process:");

        // Display resignations to be processed
        $headers = ['Employee ID', 'Name', 'Department', 'Resignation Date', 'Reason'];
        $rows = $dueResignations->map(function (Staff $staff) {
            return [
                $staff->employee_id,
                $staff->full_name,
                $staff->department?->name ?? 'N/A',
                $staff->resignation_date?->format('Y-m-d') ?? 'N/A',
                $staff->resignation_reason ? substr($staff->resignation_reason, 0, 50) . '...' : 'N/A'
            ];
        });

        $this->table($headers, $rows);

        // Check for dry run
        if ($this->option('dry-run')) {
            $this->warn('Dry run mode - no changes will be made.');
            return self::SUCCESS;
        }

        // Confirm processing unless forced
        if (!$this->option('force')) {
            if (!$this->confirm('Do you want to process these resignations?')) {
                $this->info('Processing cancelled.');
                return self::SUCCESS;
            }
        }

        $processed = 0;
        $errors = 0;

        // Process each resignation using the package's business logic
        foreach ($dueResignations as $staff) {
            try {
                $this->processStaffResignation($staff);
                $processed++;
                $this->info("✓ Processed resignation for {$staff->full_name} ({$staff->employee_id})");
            } catch (\Exception $e) {
                $errors++;
                $this->error("✗ Failed to process {$staff->full_name} ({$staff->employee_id}): {$e->getMessage()}");
            }
        }

        // Summary
        $this->info("\nProcessing complete:");
        $this->info("- Processed: {$processed}");
        if ($errors > 0) {
            $this->warn("- Errors: {$errors}");
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Process individual staff resignation using package business logic.
     */
    private function processStaffResignation(Staff $staff): void
    {
        // Delegate to the package's business logic
        $staff->processResignation();

        // Log the resignation processing
        $this->info("Resignation processed for {$staff->full_name} on {$staff->resignation_date->format('Y-m-d')}");
    }
}