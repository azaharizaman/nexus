<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Core\Actions;

use Nexus\TenancyManagement\Actions\ArchiveTenantAction;
use Nexus\TenancyManagement\Actions\CreateTenantAction;
use Nexus\TenancyManagement\Actions\UpdateTenantAction;
use Nexus\TenancyManagement\Enums\TenantStatus;
use Nexus\TenancyManagement\Events\TenantArchivedEvent;
use Nexus\TenancyManagement\Events\TenantCreatedEvent;
use Nexus\TenancyManagement\Events\TenantUpdatedEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TenantActionsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test complete tenant lifecycle: create, update, archive.
     */
    public function test_complete_tenant_lifecycle(): void
    {
        Event::fake([
            TenantCreatedEvent::class,
            TenantUpdatedEvent::class,
            TenantArchivedEvent::class,
        ]);

        // Step 1: Create tenant
        $createAction = app(CreateTenantAction::class);

        $createData = [
            'name' => 'Integration Test Tenant',
            'domain' => 'integration-test.example.com',
            'contact_email' => 'contact@integration-test.com',
            'contact_name' => 'Integration Tester',
            'subscription_plan' => 'Enterprise',
            'configuration' => [
                'timezone' => 'UTC',
                'currency' => 'USD',
            ],
        ];

        $tenant = $createAction->handle($createData);

        $this->assertNotNull($tenant->id);
        $this->assertEquals('Integration Test Tenant', $tenant->name);
        $this->assertEquals(TenantStatus::ACTIVE, $tenant->status);

        Event::assertDispatched(TenantCreatedEvent::class);

        // Step 2: Update tenant
        $updateAction = app(UpdateTenantAction::class);

        $updateData = [
            'name' => 'Updated Integration Test Tenant',
            'status' => TenantStatus::SUSPENDED->value,
            'subscription_plan' => 'Professional',
        ];

        $updatedTenant = $updateAction->handle($tenant, $updateData);

        $this->assertEquals('Updated Integration Test Tenant', $updatedTenant->name);
        $this->assertEquals(TenantStatus::SUSPENDED, $updatedTenant->status);
        $this->assertEquals('Professional', $updatedTenant->subscription_plan);
        $this->assertEquals('integration-test.example.com', $updatedTenant->domain); // Unchanged

        Event::assertDispatched(TenantUpdatedEvent::class, function ($event) {
            return $event->originalData['name'] === 'Integration Test Tenant'
                && $event->tenant->name === 'Updated Integration Test Tenant';
        });

        // Step 3: Archive tenant
        $archiveAction = app(ArchiveTenantAction::class);

        $result = $archiveAction->handle($updatedTenant);

        $this->assertTrue($result);
        $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);

        $updatedTenant->refresh();
        $this->assertEquals(TenantStatus::ARCHIVED, $updatedTenant->status);
        $this->assertNotNull($updatedTenant->deleted_at);

        Event::assertDispatched(TenantArchivedEvent::class);
    }

    /**
     * Test that actions work with dependency injection.
     */
    public function test_actions_work_with_dependency_injection(): void
    {
        Event::fake();

        // Actions should be resolvable from container
        $createAction = app(CreateTenantAction::class);
        $updateAction = app(UpdateTenantAction::class);
        $archiveAction = app(ArchiveTenantAction::class);

        $this->assertInstanceOf(CreateTenantAction::class, $createAction);
        $this->assertInstanceOf(UpdateTenantAction::class, $updateAction);
        $this->assertInstanceOf(ArchiveTenantAction::class, $archiveAction);

        // Create a tenant using injected action
        $tenant = $createAction->handle([
            'name' => 'DI Test Tenant',
            'domain' => 'di-test.example.com',
            'contact_email' => 'di@test.com',
        ]);

        $this->assertNotNull($tenant->id);

        // Update using injected action
        $updatedTenant = $updateAction->handle($tenant, [
            'name' => 'Updated DI Test Tenant',
        ]);

        $this->assertEquals('Updated DI Test Tenant', $updatedTenant->name);

        // Archive using injected action
        $result = $archiveAction->handle($tenant);

        $this->assertTrue($result);
    }

    /**
     * Test that action events contain correct data.
     */
    public function test_action_events_contain_correct_data(): void
    {
        Event::fake([TenantCreatedEvent::class, TenantUpdatedEvent::class]);

        $createAction = app(CreateTenantAction::class);

        $tenant = $createAction->handle([
            'name' => 'Event Test Tenant',
            'domain' => 'event-test.example.com',
            'contact_email' => 'event@test.com',
        ]);

        // Verify create event data
        Event::assertDispatched(TenantCreatedEvent::class, function ($event) use ($tenant) {
            return $event->tenant instanceof \Nexus\TenancyManagement\Models\Tenant
                && $event->tenant->id === $tenant->id
                && $event->tenant->name === 'Event Test Tenant';
        });

        $updateAction = app(UpdateTenantAction::class);

        $updateAction->handle($tenant, [
            'name' => 'Updated Event Test Tenant',
            'status' => TenantStatus::SUSPENDED->value,
        ]);

        // Verify update event contains both current and original data
        Event::assertDispatched(TenantUpdatedEvent::class, function ($event) use ($tenant) {
            return $event->tenant instanceof \Nexus\TenancyManagement\Models\Tenant
                && $event->tenant->id === $tenant->id
                && $event->tenant->name === 'Updated Event Test Tenant'
                && $event->tenant->status === TenantStatus::SUSPENDED
                && is_array($event->originalData)
                && $event->originalData['name'] === 'Event Test Tenant'
                && $event->originalData['status'] === TenantStatus::ACTIVE;
        });
    }

    /**
     * Test that multiple tenants can be managed independently.
     */
    public function test_multiple_tenants_can_be_managed_independently(): void
    {
        Event::fake();

        $createAction = app(CreateTenantAction::class);

        // Create first tenant
        $tenant1 = $createAction->handle([
            'name' => 'Tenant One',
            'domain' => 'tenant-one.example.com',
            'contact_email' => 'one@test.com',
        ]);

        // Create second tenant
        $tenant2 = $createAction->handle([
            'name' => 'Tenant Two',
            'domain' => 'tenant-two.example.com',
            'contact_email' => 'two@test.com',
        ]);

        // Update first tenant
        $updateAction = app(UpdateTenantAction::class);
        $updatedTenant1 = $updateAction->handle($tenant1, [
            'status' => TenantStatus::SUSPENDED->value,
        ]);

        // Archive second tenant
        $archiveAction = app(ArchiveTenantAction::class);
        $archiveAction->handle($tenant2);

        // Verify states are independent
        $this->assertEquals(TenantStatus::SUSPENDED, $updatedTenant1->status);
        $this->assertNull($updatedTenant1->deleted_at);

        $tenant2->refresh();
        $this->assertEquals(TenantStatus::ARCHIVED, $tenant2->status);
        $this->assertNotNull($tenant2->deleted_at);

        // First tenant should still be accessible
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant1->id,
            'name' => 'Tenant One',
        ]);

        // Second tenant should be soft deleted
        $this->assertSoftDeleted('tenants', [
            'id' => $tenant2->id,
        ]);
    }
}
