<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Core\Actions;

use Azaharizaman\Erp\Core\Actions\UpdateTenantAction;
use Azaharizaman\Erp\Core\Contracts\TenantRepositoryContract;
use Azaharizaman\Erp\Core\Enums\TenantStatus;
use Azaharizaman\Erp\Core\Events\TenantUpdatedEvent;
use Azaharizaman\Erp\Core\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateTenantActionTest extends TestCase
{
    use RefreshDatabase;

    protected TenantRepositoryContract $repository;

    protected UpdateTenantAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(TenantRepositoryContract::class);
        $this->action = new UpdateTenantAction($this->repository);
    }

    /**
     * Test that action can update tenant with valid data.
     */
    public function test_can_update_tenant_with_valid_data(): void
    {
        Event::fake([TenantUpdatedEvent::class]);

        $tenant = Tenant::factory()->create([
            'name' => 'Original Name',
            'domain' => 'original.example.com',
        ]);

        $data = [
            'name' => 'Updated Name',
            'contact_email' => 'updated@test.com',
        ];

        $updatedTenant = $this->action->handle($tenant, $data);

        $this->assertEquals('Updated Name', $updatedTenant->name);
        $this->assertEquals('updated@test.com', $updatedTenant->contact_email);
        $this->assertEquals('original.example.com', $updatedTenant->domain); // Unchanged

        Event::assertDispatched(TenantUpdatedEvent::class, function ($event) use ($tenant) {
            return $event->tenant->id === $tenant->id
                && $event->originalData['name'] === 'Original Name';
        });

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Updated Name',
        ]);
    }

    /**
     * Test that action updates only provided fields.
     */
    public function test_updates_only_provided_fields(): void
    {
        Event::fake();

        $tenant = Tenant::factory()->create([
            'name' => 'Original Name',
            'domain' => 'original.example.com',
            'contact_email' => 'original@test.com',
        ]);

        $data = [
            'name' => 'Updated Name',
        ];

        $updatedTenant = $this->action->handle($tenant, $data);

        $this->assertEquals('Updated Name', $updatedTenant->name);
        $this->assertEquals('original.example.com', $updatedTenant->domain);
        $this->assertEquals('original@test.com', $updatedTenant->contact_email);
    }

    /**
     * Test that action can update tenant status.
     */
    public function test_can_update_tenant_status(): void
    {
        Event::fake();

        $tenant = Tenant::factory()->create(['status' => TenantStatus::ACTIVE]);

        $data = [
            'status' => TenantStatus::SUSPENDED->value,
        ];

        $updatedTenant = $this->action->handle($tenant, $data);

        $this->assertEquals(TenantStatus::SUSPENDED, $updatedTenant->status);
    }

    /**
     * Test that action validates unique domain on update.
     */
    public function test_validates_unique_domain_on_update(): void
    {
        Event::fake();

        $tenant1 = Tenant::factory()->create(['domain' => 'tenant1.example.com']);
        $tenant2 = Tenant::factory()->create(['domain' => 'tenant2.example.com']);

        $this->expectException(ValidationException::class);

        $data = [
            'domain' => 'tenant1.example.com', // Already taken by tenant1
        ];

        $this->action->handle($tenant2, $data);
    }

    /**
     * Test that action allows updating domain to same value.
     */
    public function test_allows_updating_domain_to_same_value(): void
    {
        Event::fake();

        $tenant = Tenant::factory()->create(['domain' => 'tenant.example.com']);

        $data = [
            'domain' => 'tenant.example.com', // Same as current
            'name' => 'Updated Name',
        ];

        $updatedTenant = $this->action->handle($tenant, $data);

        $this->assertEquals('tenant.example.com', $updatedTenant->domain);
        $this->assertEquals('Updated Name', $updatedTenant->name);
    }

    /**
     * Test that action validates email format on update.
     */
    public function test_validates_email_format_on_update(): void
    {
        $tenant = Tenant::factory()->create();

        $this->expectException(ValidationException::class);

        $data = [
            'contact_email' => 'invalid-email',
        ];

        $this->action->handle($tenant, $data);
    }

    /**
     * Test that action validates status enum values on update.
     */
    public function test_validates_status_enum_values_on_update(): void
    {
        $tenant = Tenant::factory()->create();

        $this->expectException(ValidationException::class);

        $data = [
            'status' => 'invalid_status',
        ];

        $this->action->handle($tenant, $data);
    }

    /**
     * Test that action can update configuration.
     */
    public function test_can_update_configuration(): void
    {
        Event::fake();

        $tenant = Tenant::factory()->create([
            'configuration' => [
                'timezone' => 'UTC',
                'currency' => 'USD',
            ],
        ]);

        $data = [
            'configuration' => [
                'timezone' => 'America/New_York',
                'currency' => 'USD',
                'locale' => 'en_US',
            ],
        ];

        $updatedTenant = $this->action->handle($tenant, $data);

        $this->assertEquals('America/New_York', $updatedTenant->configuration['timezone']);
        $this->assertEquals('en_US', $updatedTenant->configuration['locale']);
    }

    /**
     * Test that action includes original data in event.
     */
    public function test_includes_original_data_in_event(): void
    {
        Event::fake([TenantUpdatedEvent::class]);

        $tenant = Tenant::factory()->create([
            'name' => 'Original Name',
            'domain' => 'original.example.com',
            'status' => TenantStatus::ACTIVE,
        ]);

        $data = [
            'name' => 'Updated Name',
            'status' => TenantStatus::SUSPENDED->value,
        ];

        $this->action->handle($tenant, $data);

        Event::assertDispatched(TenantUpdatedEvent::class, function ($event) {
            return $event->originalData['name'] === 'Original Name'
                && $event->originalData['status'] === TenantStatus::ACTIVE
                && $event->tenant->name === 'Updated Name'
                && $event->tenant->status === TenantStatus::SUSPENDED;
        });
    }
}
