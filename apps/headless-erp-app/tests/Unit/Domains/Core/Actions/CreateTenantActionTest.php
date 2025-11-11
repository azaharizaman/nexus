<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Core\Actions;

use Azaharizaman\Erp\Core\Actions\CreateTenantAction;
use Azaharizaman\Erp\Core\Contracts\TenantRepositoryContract;
use Azaharizaman\Erp\Core\Enums\TenantStatus;
use Azaharizaman\Erp\Core\Events\TenantCreatedEvent;
use Azaharizaman\Erp\Core\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateTenantActionTest extends TestCase
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
     * Test that action can create a tenant with valid data.
     */
    public function test_can_create_tenant_with_valid_data(): void
    {
        Event::fake([TenantCreatedEvent::class]);

        $data = [
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
            'contact_email' => 'contact@test.com',
            'contact_name' => 'John Doe',
            'contact_phone' => '+1234567890',
        ];

        $tenant = $this->action->handle($data);

        $this->assertInstanceOf(Tenant::class, $tenant);
        $this->assertEquals('Test Tenant', $tenant->name);
        $this->assertEquals('test.example.com', $tenant->domain);
        $this->assertEquals('contact@test.com', $tenant->contact_email);
        $this->assertEquals(TenantStatus::ACTIVE, $tenant->status);

        Event::assertDispatched(TenantCreatedEvent::class, function ($event) use ($tenant) {
            return $event->tenant->id === $tenant->id;
        });

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
        ]);
    }

    /**
     * Test that action sets default status to ACTIVE.
     */
    public function test_sets_default_status_to_active(): void
    {
        Event::fake();

        $data = [
            'name' => 'Test Tenant',
            'domain' => 'test2.example.com',
            'contact_email' => 'contact@test2.com',
        ];

        $tenant = $this->action->handle($data);

        $this->assertEquals(TenantStatus::ACTIVE, $tenant->status);
    }

    /**
     * Test that action accepts custom status.
     */
    public function test_accepts_custom_status(): void
    {
        Event::fake();

        $data = [
            'name' => 'Test Tenant',
            'domain' => 'test3.example.com',
            'contact_email' => 'contact@test3.com',
            'status' => TenantStatus::SUSPENDED->value,
        ];

        $tenant = $this->action->handle($data);

        $this->assertEquals(TenantStatus::SUSPENDED, $tenant->status);
    }

    /**
     * Test that action validates required fields.
     */
    public function test_validates_required_fields(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'name' => 'Test Tenant',
            // Missing domain and contact_email
        ];

        $this->action->handle($data);
    }

    /**
     * Test that action validates unique domain.
     */
    public function test_validates_unique_domain(): void
    {
        Event::fake();

        // Create first tenant
        Tenant::factory()->create(['domain' => 'duplicate.example.com']);

        $this->expectException(ValidationException::class);

        $data = [
            'name' => 'Test Tenant',
            'domain' => 'duplicate.example.com',
            'contact_email' => 'contact@test.com',
        ];

        $this->action->handle($data);
    }

    /**
     * Test that action validates email format.
     */
    public function test_validates_email_format(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'name' => 'Test Tenant',
            'domain' => 'test4.example.com',
            'contact_email' => 'invalid-email',
        ];

        $this->action->handle($data);
    }

    /**
     * Test that action validates status enum values.
     */
    public function test_validates_status_enum_values(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'name' => 'Test Tenant',
            'domain' => 'test5.example.com',
            'contact_email' => 'contact@test5.com',
            'status' => 'invalid_status',
        ];

        $this->action->handle($data);
    }

    /**
     * Test that action handles optional fields.
     */
    public function test_handles_optional_fields(): void
    {
        Event::fake();

        $data = [
            'name' => 'Test Tenant',
            'domain' => 'test6.example.com',
            'contact_email' => 'contact@test6.com',
            'subscription_plan' => 'Enterprise',
            'billing_email' => 'billing@test6.com',
            'configuration' => [
                'timezone' => 'UTC',
                'currency' => 'USD',
            ],
        ];

        $tenant = $this->action->handle($data);

        $this->assertEquals('Enterprise', $tenant->subscription_plan);
        $this->assertEquals('billing@test6.com', $tenant->billing_email);
        $this->assertIsArray($tenant->configuration);
        $this->assertEquals('UTC', $tenant->configuration['timezone']);
    }
}
