<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Core\Listeners;

use Azaharizaman\Erp\Core\Actions\CreateTenantAction;
use Azaharizaman\Erp\Core\Contracts\TenantRepositoryContract;
use Azaharizaman\Erp\Core\Events\TenantCreatedEvent;
use Azaharizaman\Erp\Core\Listeners\InitializeTenantDataListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TenantLifecycleListenersIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected TenantRepositoryContract $repository;

    protected CreateTenantAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(TenantRepositoryContract::class);
        $this->action = new CreateTenantAction($this->repository);
    }

    /**
     * Test that creating a tenant dispatches event and triggers listener.
     */
    public function test_creating_tenant_dispatches_event_and_triggers_listener(): void
    {
        Event::fake([TenantCreatedEvent::class]);

        $data = [
            'name' => 'Integration Test Tenant',
            'domain' => 'integration.example.com',
            'contact_email' => 'integration@test.com',
        ];

        $tenant = $this->action->handle($data);

        // Verify event was dispatched
        Event::assertDispatched(TenantCreatedEvent::class, function ($event) use ($tenant) {
            return $event->tenant->id === $tenant->id;
        });
    }

    /**
     * Test that listener is registered and called when event is dispatched.
     */
    public function test_listener_is_registered_and_called(): void
    {
        // Don't fake events - we want them to run
        Log::shouldReceive('info')
            ->twice()
            ->withArgs(function ($message) {
                return str_contains($message, 'Initializing data for tenant') ||
                       str_contains($message, 'Successfully initialized data for tenant');
            });

        Log::shouldReceive('debug')->times(3);

        $data = [
            'name' => 'Listener Test Tenant',
            'domain' => 'listener.example.com',
            'contact_email' => 'listener@test.com',
        ];

        $tenant = $this->action->handle($data);

        $this->assertNotNull($tenant);
    }

    /**
     * Test that listener can be invoked directly.
     */
    public function test_listener_can_be_invoked_directly(): void
    {
        $data = [
            'name' => 'Direct Test Tenant',
            'domain' => 'direct.example.com',
            'contact_email' => 'direct@test.com',
        ];

        $tenant = $this->action->handle($data);

        Log::shouldReceive('info')->twice();
        Log::shouldReceive('debug')->times(3);

        $listener = new InitializeTenantDataListener;
        $event = new TenantCreatedEvent($tenant);

        $listener->handle($event);

        $this->assertTrue(true);
    }

    /**
     * Test that event listener mapping is configured correctly.
     */
    public function test_event_listener_mapping_is_configured(): void
    {
        $listeners = Event::getRawListeners();

        $hasListener = false;

        if (isset($listeners[TenantCreatedEvent::class])) {
            foreach ($listeners[TenantCreatedEvent::class] as $listener) {
                if (is_string($listener) && $listener === InitializeTenantDataListener::class) {
                    $hasListener = true;
                    break;
                }
            }
        }

        $this->assertTrue(
            $hasListener,
            'InitializeTenantDataListener should be registered for TenantCreatedEvent'
        );
    }

    /**
     * Test that multiple tenants can be created and initialized.
     */
    public function test_multiple_tenants_can_be_created_and_initialized(): void
    {
        Log::shouldReceive('info')->times(6); // 3 tenants * 2 log entries each
        Log::shouldReceive('debug')->times(9); // 3 tenants * 3 log entries each

        for ($i = 1; $i <= 3; $i++) {
            $data = [
                'name' => "Tenant {$i}",
                'domain' => "tenant{$i}.example.com",
                'contact_email' => "tenant{$i}@test.com",
            ];

            $tenant = $this->action->handle($data);
            $this->assertNotNull($tenant);
        }

        $this->assertDatabaseCount('tenants', 3);
    }

    /**
     * Test that listener handles tenant with all required fields.
     */
    public function test_listener_handles_tenant_with_all_fields(): void
    {
        Log::shouldReceive('info')->twice();
        Log::shouldReceive('debug')->times(3);

        $data = [
            'name' => 'Complete Tenant',
            'domain' => 'complete.example.com',
            'contact_email' => 'complete@test.com',
            'contact_name' => 'John Doe',
            'contact_phone' => '+1234567890',
            'billing_email' => 'billing@complete.com',
            'subscription_plan' => 'Enterprise',
            'configuration' => [
                'timezone' => 'UTC',
                'currency' => 'USD',
            ],
        ];

        $tenant = $this->action->handle($data);

        $this->assertNotNull($tenant);
        $this->assertEquals('Complete Tenant', $tenant->name);
    }

    /**
     * Test that listener is queued for async execution.
     */
    public function test_listener_is_queued(): void
    {
        $listener = new InitializeTenantDataListener;

        $this->assertInstanceOf(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            $listener,
            'InitializeTenantDataListener should implement ShouldQueue for async execution'
        );
    }
}
