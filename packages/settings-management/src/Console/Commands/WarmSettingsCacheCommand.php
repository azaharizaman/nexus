<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SettingsManagement\Console\Commands;

use Azaharizaman\Erp\SettingsManagement\Contracts\SettingsServiceContract;
use Illuminate\Console\Command;

/**
 * Warm Settings Cache Command
 *
 * Pre-loads frequently accessed settings into cache for improved performance.
 */
class WarmSettingsCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:settings:warm-cache
                            {--scope= : Specific scope to warm (system, tenant, module, user)}
                            {--tenant= : Specific tenant ID to warm}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm the settings cache by pre-loading settings into Redis/Memcached';

    /**
     * Execute the console command.
     *
     * @param SettingsServiceContract $settingsService
     * @return int
     */
    public function handle(SettingsServiceContract $settingsService): int
    {
        if (!config('settings-management.cache.enabled', true)) {
            $this->warn('Settings cache is disabled in configuration.');
            return Command::FAILURE;
        }

        $scope = $this->option('scope');
        $tenantId = $this->option('tenant') ? (int) $this->option('tenant') : null;

        $this->info('Warming settings cache...');

        try {
            $count = $settingsService->warmCache($scope, $tenantId);

            $scopeText = $scope ?? 'all scopes';
            $tenantText = $tenantId ? " for tenant {$tenantId}" : '';

            $this->info("Successfully cached {$count} settings for {$scopeText}{$tenantText}.");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to warm cache: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
