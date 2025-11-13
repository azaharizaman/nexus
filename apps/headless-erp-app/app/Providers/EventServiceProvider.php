<?php

declare(strict_types=1);

namespace Nexus\Erp\Providers;

use Nexus\TenancyManagement\Events\TenantArchivedEvent;
use Nexus\TenancyManagement\Events\TenantCreatedEvent;
use Nexus\TenancyManagement\Events\TenantUpdatedEvent;
use Nexus\TenancyManagement\Listeners\InitializeTenantDataListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Event Service Provider
 *
 * Registers event listeners and subscribers for the application.
 * Enables event-driven architecture for tenant lifecycle and other domain events.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Tenant Lifecycle Events
        TenantCreatedEvent::class => [
            InitializeTenantDataListener::class,
        ],

        TenantUpdatedEvent::class => [
            // Add listeners here when needed
            // e.g., UpdateTenantCacheListener::class,
        ],

        TenantArchivedEvent::class => [
            // Add listeners here when needed
            // e.g., CleanupTenantDataListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
