<?php

declare(strict_types=1);

namespace Nexus\Atomy;

use Illuminate\Support\ServiceProvider;
use Nexus\Atomy\Support\Contracts\ActivityLoggerContract;
use Nexus\Atomy\Support\Contracts\PermissionServiceContract;
use Nexus\Atomy\Support\Contracts\SearchServiceContract;
use Nexus\Atomy\Support\Contracts\TokenServiceContract;
use Nexus\Atomy\Support\Services\Auth\SanctumTokenService;
use Nexus\Atomy\Support\Services\Logging\SpatieActivityLogger;
use Nexus\Erp\Support\Contracts\ActivityLoggerContract;
use Nexus\AuditLog\Contracts\AuditLogRepositoryContract;
use Nexus\Atomy\Support\Services\Logging\SpatieActivityLoggerAdapter;
use Nexus\Atomy\Support\Services\Permission\SpatiePermissionService;
use Nexus\Atomy\Support\Services\Search\ScoutSearchService;
use Nexus\Hrm\Contracts\OrganizationServiceContract as HrmOrganizationServiceContract;
use Nexus\OrgStructure\Contracts\OrganizationServiceContract as OrgOrganizationServiceContract;
use Nexus\OrgStructure\Services\DefaultOrganizationService;
use Nexus\Atomy\Support\Adapters\OrgStructure\OrganizationServiceAdapter;

/**
 * ERP Service Provider
 *
 * Main service provider for the Nexus ERP package.
 * Registers all core ERP services, middleware, and components.
 *
 * @package Nexus\Atomy
 */
class AtomyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Merge Nexus ERP configuration FIRST
        $this->mergeConfigFrom(
            __DIR__.'/../config/nexus.php',
            'nexus'
        );

        // Register service contracts
        $this->registerContracts();

        // Register atomic package orchestration providers
        $this->registerOrchestrationProviders();

        // Merge package configuration (if exists)
        if (file_exists(__DIR__.'/../apps/edward/config/app.php')) {
            $this->mergeConfigFrom(
                __DIR__.'/../apps/edward/config/app.php',
                'nexus-erp'
            );
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Load helper functions
        require_once __DIR__.'/Support/Helpers/tenant.php';

        // Load Nexus ERP routes
        $this->loadRoutesFrom(__DIR__.'/../routes/audit-log.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/console.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/api-backoffice.php');

        // Load routes (if api.php exists in Edward)
        if (file_exists(__DIR__.'/../apps/edward/routes/api.php')) {
            $this->loadRoutesFrom(__DIR__.'/../apps/edward/routes/api.php');
        }

        // Load migrations (if migrations directory exists in Edward)
        if (is_dir(__DIR__.'/../apps/edward/database/migrations')) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        // Publish configuration (optional, commented for CLI-only apps)
        // if ($this->app->runningInConsole()) {
        //     $this->publishes([
        //         __DIR__.'/../apps/edward/config/app.php' => config_path('nexus-erp.php'),
        //     ], 'nexus-erp-config');
        //
        //     $this->publishes([
        //         __DIR__.'/../apps/edward/database/migrations' => database_path('migrations'),
        //     ], 'nexus-erp-migrations');
        // }
    }

    /**
     * Register service contracts
     *
     * @return void
     */
    protected function registerContracts(): void
    {
        // Register Spatie Activity Logger Adapter
        $this->app->singleton(SpatieActivityLoggerAdapter::class, function ($app) {
            return new SpatieActivityLoggerAdapter(
                $app->make(AuditLogRepositoryContract::class)
            );
        });

        // Activity Logger
        $this->app->singleton(ActivityLoggerContract::class, function ($app) {
            return new SpatieActivityLogger(
                $app->make(AuditLogRepositoryContract::class),
                $app->make(SpatieActivityLoggerAdapter::class)
            );
        });

        // Search Service
        $this->app->singleton(SearchServiceContract::class, function ($app) {
            return new ScoutSearchService();
        });

        // Token Service
        $this->app->singleton(TokenServiceContract::class, function ($app) {
            return new SanctumTokenService();
        });

        // Permission Service
        $this->app->singleton(PermissionServiceContract::class, function ($app) {
            return new SpatiePermissionService();
        });

        // Organization Structure Service binding for HRM contract via adapter
        if (class_exists(DefaultOrganizationService::class) && interface_exists(HrmOrganizationServiceContract::class)) {
            // Bind underlying org service if not already
            $this->app->singleton(OrgOrganizationServiceContract::class, DefaultOrganizationService::class);

            // Bind HRM-facing contract to adapter
            $this->app->singleton(HrmOrganizationServiceContract::class, function ($app) {
                return new OrganizationServiceAdapter($app->make(OrgOrganizationServiceContract::class));
            });
        }

        // Sequencing Services - Atomic serial number generation
        $this->app->singleton(\Nexus\Sequencing\Contracts\SequenceRepositoryInterface::class, \App\Repositories\Sequencing\SequenceRepository::class);
        $this->app->singleton(\Nexus\Sequencing\Contracts\SerialNumberLogRepositoryInterface::class, \App\Repositories\Sequencing\SerialNumberLogRepository::class);

        // Sequencing Service bindings
        $this->app->singleton(\Nexus\Sequencing\Contracts\PatternParserServiceInterface::class, \App\Services\Sequencing\PatternParserService::class);
        $this->app->singleton(\Nexus\Sequencing\Contracts\GenerationServiceInterface::class, \App\Services\Sequencing\GenerationService::class);
        // Project Management Services
        $this->app->singleton(\Nexus\ProjectManagement\Contracts\ProjectRepositoryInterface::class, \Nexus\Atomy\Repositories\DbProjectRepository::class);
        $this->app->singleton(\Nexus\ProjectManagement\Contracts\TaskRepositoryInterface::class, \Nexus\Atomy\Repositories\DbTaskRepository::class);
        $this->app->singleton(\Nexus\ProjectManagement\Contracts\TimesheetRepositoryInterface::class, \Nexus\Atomy\Repositories\DbTimesheetRepository::class);
        $this->app->singleton(\Nexus\ProjectManagement\Contracts\MilestoneRepositoryInterface::class, \Nexus\Atomy\Repositories\DbMilestoneRepository::class);
        $this->app->singleton(\Nexus\ProjectManagement\Contracts\ResourceAllocationRepositoryInterface::class, \Nexus\Atomy\Repositories\DbResourceAllocationRepository::class);
        $this->app->singleton(\Nexus\ProjectManagement\Contracts\ExpenseRepositoryInterface::class, \Nexus\Atomy\Repositories\DbExpenseRepository::class);
        $this->app->singleton(\Nexus\ProjectManagement\Contracts\InvoiceRepositoryInterface::class, \Nexus\Atomy\Repositories\DbInvoiceRepository::class);
        $this->app->singleton(\Nexus\ProjectManagement\Contracts\TaskDependencyRepositoryInterface::class, \Nexus\Atomy\Repositories\DbTaskDependencyRepository::class);
        // Accounting repository bindings
        $this->app->singleton(\Nexus\Accounting\Contracts\AccountRepositoryInterface::class, \Nexus\Atomy\Repositories\Accounting\DbAccountRepository::class);
        $this->app->singleton(\Nexus\Accounting\Contracts\JournalRepositoryInterface::class, \Nexus\Atomy\Repositories\Accounting\DbJournalRepository::class);
        // Managers
        $this->app->singleton(\Nexus\ProjectManagement\Services\TaskManager::class, function ($app) {
            return new \Nexus\ProjectManagement\Services\TaskManager(
                $app->make(\Nexus\ProjectManagement\Contracts\TaskRepositoryInterface::class),
                $app->make(\Nexus\ProjectManagement\Contracts\TaskDependencyRepositoryInterface::class)
            );
        });
        $this->app->singleton(\Nexus\ProjectManagement\Services\BudgetManager::class, function ($app) {
            return new \Nexus\ProjectManagement\Services\BudgetManager(
                $app->make(\Nexus\ProjectManagement\Contracts\TimesheetRepositoryInterface::class),
                $app->make(\Nexus\ProjectManagement\Contracts\ExpenseRepositoryInterface::class),
                $app->make(\Nexus\ProjectManagement\Contracts\BillingRateProviderInterface::class)
            );
        });
        $this->app->singleton(\Nexus\ProjectManagement\Services\ResourceManager::class, function ($app) {
            return new \Nexus\ProjectManagement\Services\ResourceManager($app->make(\Nexus\ProjectManagement\Contracts\ResourceAllocationRepositoryInterface::class));
        });
        $this->app->singleton(\Nexus\ProjectManagement\Services\MilestoneManager::class, function ($app) {
            return new \Nexus\ProjectManagement\Services\MilestoneManager($app->make(\Nexus\ProjectManagement\Contracts\MilestoneRepositoryInterface::class));
        });

        // Accounting Manager
        $this->app->singleton(\Nexus\Accounting\Services\AccountingManager::class, function ($app) {
            return new \Nexus\Accounting\Services\AccountingManager(
                $app->make(\Nexus\Accounting\Contracts\AccountRepositoryInterface::class),
                $app->make(\Nexus\Accounting\Contracts\JournalRepositoryInterface::class),
                $app->make(\Nexus\Atomy\Support\Contracts\ActivityLoggerContract::class)
            );
        });
        // Analytics engine & manager
        $this->app->singleton(\Nexus\Analytics\Core\Contracts\AnalyticsEngineContract::class, function ($app) {
            return new \Nexus\Analytics\Core\Engine\AnalyticsEngine();
        });

        $this->app->singleton(\Nexus\Atomy\Services\Analytics\AnalyticsManager::class, function ($app) {
            return new \Nexus\Atomy\Services\Analytics\AnalyticsManager(
                $app->make(\Nexus\Analytics\Core\Contracts\AnalyticsEngineContract::class),
                $app->make(\Nexus\Atomy\Support\Contracts\ActivityLoggerContract::class)
            );
        });
        // Billing rate provider: simple default returns app config or default value
        $this->app->singleton(\Nexus\ProjectManagement\Contracts\BillingRateProviderInterface::class, function ($app) {
            return new class {
                public function getHourlyRateForUser(int $userId): float
                {
                    return config('nexus.project.default_hourly_rate', 50);
                }
            };
        });
    }

    /**
     * Register atomic package orchestration providers.
     *
     * @return void
     */
    protected function registerOrchestrationProviders(): void
    {
        // Check if auto-registration is enabled
        if (!config('nexus.packages.auto_register', true)) {
            return;
        }

        // Register orchestration providers for atomic packages
        $providers = [];

        // Backoffice orchestration provider
        if (config('nexus.packages.enabled.backoffice', true)) {
            $providers[] = \Nexus\Atomy\Providers\BackofficeServiceProvider::class;
        }

        // CRM orchestration provider
        if (config('nexus.packages.enabled.crm', false)) {
            $providers[] = \Nexus\Atomy\Providers\CrmServiceProvider::class;
        }

        // Register all orchestration providers
        foreach ($providers as $provider) {
            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }
}
