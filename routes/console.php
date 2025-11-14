<?php

declare(strict_types=1);

use Nexus\Erp\Actions\AuditLog\PurgeExpiredAuditLogsAction;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Audit Logging Console Commands
|--------------------------------------------------------------------------
|
| Console commands for audit log maintenance tasks.
| These commands are orchestrated through Laravel Actions.
|
*/

// Register purge command through Action pattern
Artisan::command('audit-log:purge-expired {--days=30 : Days to keep audit logs}', function () {
    /** @var \Illuminate\Console\Command $this */
    $days = (int) $this->option('days');
    
    $this->info("Starting audit log purge for entries older than {$days} days...");
    
    try {
        $result = app(PurgeExpiredAuditLogsAction::class)->handle($days);
        
        $this->info("Purged {$result['purged_count']} audit log entries.");
        $this->line("Cutoff date: " . ($result['cutoff_date'] instanceof \Carbon\Carbon ? $result['cutoff_date']->toDateTimeString() : $result['cutoff_date']));
        $this->line("Tenant ID: " . ($result['tenant_id'] ?? 'All tenants'));
        $this->line("Dry run: " . ($result['dry_run'] ? 'Yes' : 'No'));
        
    } catch (\Exception $e) {
        $this->error("Failed to purge audit logs: {$e->getMessage()}");
        return 1;
    }
    
    return 0;
})
->purpose('Purge expired audit log entries')
->hourly();