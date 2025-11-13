<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Core\Actions;

use Nexus\TenancyManagement\Actions\ArchiveTenantAction;
use Nexus\TenancyManagement\Contracts\TenantRepositoryContract;
use Nexus\TenancyManagement\Enums\TenantStatus;
use Nexus\TenancyManagement\Events\TenantArchivedEvent;
use Nexus\TenancyManagement\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ArchiveTenantActionTest extends TestCase
{
    use RefreshDatabase;

    protected TenantRepositoryContract $repository;

    protected ArchiveTenantAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(TenantRepositoryContract::class);
        $this->action = new ArchiveTenantAction($this->repository);
    }

    /**
     * Test that action can archive a tenant.
     */
    public function test_can_archive_tenant(): void
    {
        Event::fake([TenantArchivedEvent::class]);

        $tenant = Tenant::factory()->create([
            'status' => TenantStatus::ACTIVE,
        ]);

        $result = $this->action->handle($tenant);

        $this->assertTrue($result);
        $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);

        // Refresh to get updated values including deleted_at
        $tenant->refresh();

        $this->assertEquals(TenantStatus::ARCHIVED, $tenant->status);
        $this->assertNotNull($tenant->deleted_at);

        Event::assertDispatched(TenantArchivedEvent::class, function ($event) use ($tenant) {
            return $event->tenant->id === $tenant->id;
        });
    }

    /**
     * Test that action updates status to ARCHIVED before soft delete.
     */
    public function test_updates_status_before_soft_delete(): void
    {
        Event::fake();

        $tenant = Tenant::factory()->create([
            'status' => TenantStatus::ACTIVE,
        ]);

        $this->action->handle($tenant);

        $tenant->refresh();

        $this->assertEquals(TenantStatus::ARCHIVED, $tenant->status);
    }

    /**
     * Test that action can archive suspended tenant.
     */
    public function test_can_archive_suspended_tenant(): void
    {
        Event::fake([TenantArchivedEvent::class]);

        $tenant = Tenant::factory()->suspended()->create();

        $result = $this->action->handle($tenant);

        $this->assertTrue($result);
        $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);

        Event::assertDispatched(TenantArchivedEvent::class);
    }

    /**
     * Test that action dispatches event after successful archive.
     */
    public function test_dispatches_event_after_successful_archive(): void
    {
        Event::fake([TenantArchivedEvent::class]);

        $tenant = Tenant::factory()->create();

        $this->action->handle($tenant);

        Event::assertDispatched(TenantArchivedEvent::class, function ($event) use ($tenant) {
            return $event->tenant->id === $tenant->id;
        });
    }

    /**
     * Test that archived tenant can still be retrieved with trashed().
     */
    public function test_archived_tenant_can_be_retrieved_with_trashed(): void
    {
        Event::fake();

        $tenant = Tenant::factory()->create();
        $tenantId = $tenant->id;

        $this->action->handle($tenant);

        $this->assertNull(Tenant::find($tenantId));
        $this->assertNotNull(Tenant::withTrashed()->find($tenantId));
    }

    /**
     * Test that action preserves tenant data after archiving.
     */
    public function test_preserves_tenant_data_after_archiving(): void
    {
        Event::fake();

        $tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
            'contact_email' => 'contact@test.com',
        ]);

        $this->action->handle($tenant);

        $archivedTenant = Tenant::withTrashed()->find($tenant->id);

        $this->assertEquals('Test Tenant', $archivedTenant->name);
        $this->assertEquals('test.example.com', $archivedTenant->domain);
        $this->assertEquals('contact@test.com', $archivedTenant->contact_email);
    }

    /**
     * Test that action works with already archived tenant.
     */
    public function test_works_with_already_archived_tenant(): void
    {
        Event::fake();

        $tenant = Tenant::factory()->archived()->create();
        $tenant->delete(); // Soft delete

        // Try to archive again
        $result = $this->action->handle($tenant);

        $this->assertTrue($result);
        $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);
    }
}
