<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Core\Listeners;

use Nexus\Erp\Core\Events\TenantCreatedEvent;
use Nexus\Erp\Core\Listeners\InitializeTenantDataListener;
use Nexus\Erp\Core\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class InitializeTenantDataListenerTest extends TestCase
{
    use RefreshDatabase;

    protected InitializeTenantDataListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new InitializeTenantDataListener();
    }

    /**
     * Test that listener can be instantiated.
     */
    public function test_can_instantiate_listener(): void
    {
        $this->assertInstanceOf(InitializeTenantDataListener::class, $this->listener);
    }

    /**
     * Test that listener implements ShouldQueue interface.
     */
    public function test_implements_should_queue_interface(): void
    {
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $this->listener);
    }

    /**
     * Test that listener handles tenant created event.
     */
    public function test_handles_tenant_created_event(): void
    {
        Log::shouldReceive('info')
            ->twice()
            ->withArgs(function ($message) {
                return str_contains($message, 'Initializing data for tenant') ||
                       str_contains($message, 'Successfully initialized data for tenant');
            });

        Log::shouldReceive('debug')
            ->times(3)
            ->withArgs(function ($message, $context) {
                return (str_contains($message, 'Default roles creation pending') ||
                        str_contains($message, 'Default permissions creation pending') ||
                        str_contains($message, 'Default settings creation pending')) &&
                       isset($context['tenant_id']);
            });

        $tenant = Tenant::factory()->create();
        $event = new TenantCreatedEvent($tenant);

        $this->listener->handle($event);

        // Since the listener only logs for now (pending integrations),
        // we verify that it executed without errors
        $this->assertTrue(true);
    }

    /**
     * Test that listener logs tenant initialization start.
     */
    public function test_logs_tenant_initialization_start(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Test Tenant']);

        Log::shouldReceive('info')
            ->once()
            ->with("Initializing data for tenant: Test Tenant ({$tenant->id})");

        Log::shouldReceive('debug')->times(3);
        Log::shouldReceive('info')->once()->with(\Mockery::pattern('/Successfully initialized/'));

        $event = new TenantCreatedEvent($tenant);
        $this->listener->handle($event);
    }

    /**
     * Test that listener logs tenant initialization completion.
     */
    public function test_logs_tenant_initialization_completion(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Test Tenant']);

        Log::shouldReceive('info')->once()->with(\Mockery::pattern('/Initializing data/'));
        Log::shouldReceive('debug')->times(3);

        Log::shouldReceive('info')
            ->once()
            ->with("Successfully initialized data for tenant: Test Tenant ({$tenant->id})");

        $event = new TenantCreatedEvent($tenant);
        $this->listener->handle($event);
    }

    /**
     * Test that listener logs role creation pending status.
     */
    public function test_logs_role_creation_pending(): void
    {
        $tenant = Tenant::factory()->create();

        Log::shouldReceive('info')->twice();

        Log::shouldReceive('debug')
            ->once()
            ->with(
                'Default roles creation pending - awaiting spatie/laravel-permission integration',
                ['tenant_id' => $tenant->id]
            );

        Log::shouldReceive('debug')->twice(); // For permissions and settings

        $event = new TenantCreatedEvent($tenant);
        $this->listener->handle($event);
    }

    /**
     * Test that listener logs permission creation pending status.
     */
    public function test_logs_permission_creation_pending(): void
    {
        $tenant = Tenant::factory()->create();

        Log::shouldReceive('info')->twice();
        Log::shouldReceive('debug')->once(); // For roles

        Log::shouldReceive('debug')
            ->once()
            ->with(
                'Default permissions creation pending - awaiting spatie/laravel-permission integration',
                ['tenant_id' => $tenant->id]
            );

        Log::shouldReceive('debug')->once(); // For settings

        $event = new TenantCreatedEvent($tenant);
        $this->listener->handle($event);
    }

    /**
     * Test that listener logs settings creation pending status.
     */
    public function test_logs_settings_creation_pending(): void
    {
        $tenant = Tenant::factory()->create();

        Log::shouldReceive('info')->twice();
        Log::shouldReceive('debug')->twice(); // For roles and permissions

        Log::shouldReceive('debug')
            ->once()
            ->with(
                'Default settings creation pending - awaiting Settings implementation',
                ['tenant_id' => $tenant->id]
            );

        $event = new TenantCreatedEvent($tenant);
        $this->listener->handle($event);
    }

    /**
     * Test that listener can handle event with all tenant types.
     */
    public function test_handles_different_tenant_types(): void
    {
        Log::shouldReceive('info')->times(6);
        Log::shouldReceive('debug')->times(9);

        // Active tenant
        $activeTenant = Tenant::factory()->create();
        $this->listener->handle(new TenantCreatedEvent($activeTenant));

        // Suspended tenant
        $suspendedTenant = Tenant::factory()->suspended()->create();
        $this->listener->handle(new TenantCreatedEvent($suspendedTenant));

        // Archived tenant
        $archivedTenant = Tenant::factory()->archived()->create();
        $this->listener->handle(new TenantCreatedEvent($archivedTenant));

        $this->assertTrue(true);
    }

    /**
     * Test that listener executes all initialization methods.
     */
    public function test_executes_all_initialization_methods(): void
    {
        $tenant = Tenant::factory()->create();

        Log::shouldReceive('info')
            ->once()
            ->with(\Mockery::pattern('/Initializing data/'));

        // Expect debug logs for each initialization method
        Log::shouldReceive('debug')
            ->once()
            ->with(\Mockery::pattern('/Default roles/'), \Mockery::type('array'));

        Log::shouldReceive('debug')
            ->once()
            ->with(\Mockery::pattern('/Default permissions/'), \Mockery::type('array'));

        Log::shouldReceive('debug')
            ->once()
            ->with(\Mockery::pattern('/Default settings/'), \Mockery::type('array'));

        Log::shouldReceive('info')
            ->once()
            ->with(\Mockery::pattern('/Successfully initialized/'));

        $event = new TenantCreatedEvent($tenant);
        $this->listener->handle($event);
    }
}
