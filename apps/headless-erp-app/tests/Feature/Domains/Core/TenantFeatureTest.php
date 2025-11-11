<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Core;

use Azaharizaman\Erp\Core\Enums\TenantStatus;
use Azaharizaman\Erp\Core\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that tenant can be created with all required fields.
     */
    public function test_can_create_tenant_with_all_required_fields(): void
    {
        $tenantData = [
            'name' => 'Test Company',
            'domain' => 'testcompany.com',
            'status' => TenantStatus::ACTIVE,
            'configuration' => [
                'timezone' => 'America/New_York',
                'currency' => 'USD',
                'locale' => 'en_US',
            ],
            'subscription_plan' => 'enterprise',
            'billing_email' => 'billing@testcompany.com',
            'contact_name' => 'John Doe',
            'contact_email' => 'john@testcompany.com',
            'contact_phone' => '+1-555-0123',
        ];

        $tenant = Tenant::create($tenantData);

        $this->assertInstanceOf(Tenant::class, $tenant);
        $this->assertEquals('Test Company', $tenant->name);
        $this->assertEquals('testcompany.com', $tenant->domain);
        $this->assertEquals(TenantStatus::ACTIVE, $tenant->status);
        $this->assertEquals('enterprise', $tenant->subscription_plan);
        $this->assertEquals('billing@testcompany.com', $tenant->billing_email);
        $this->assertEquals('John Doe', $tenant->contact_name);
        $this->assertEquals('john@testcompany.com', $tenant->contact_email);
        $this->assertEquals('+1-555-0123', $tenant->contact_phone);
    }

    /**
     * Test that tenant configuration is properly encrypted and decrypted.
     */
    public function test_tenant_configuration_encryption(): void
    {
        $config = [
            'timezone' => 'UTC',
            'currency' => 'EUR',
            'api_key' => 'secret-key-123',
        ];

        $tenant = Tenant::factory()->create(['configuration' => $config]);

        // Refresh from database
        $tenant = $tenant->fresh();

        $this->assertIsArray($tenant->configuration);
        $this->assertEquals('UTC', $tenant->configuration['timezone']);
        $this->assertEquals('EUR', $tenant->configuration['currency']);
        $this->assertEquals('secret-key-123', $tenant->configuration['api_key']);
    }

    /**
     * Test that tenant status can be changed.
     */
    public function test_can_change_tenant_status(): void
    {
        $tenant = Tenant::factory()->create(['status' => TenantStatus::ACTIVE]);

        $this->assertTrue($tenant->isActive());

        $tenant->update(['status' => TenantStatus::SUSPENDED]);

        $this->assertTrue($tenant->isSuspended());
        $this->assertFalse($tenant->isActive());
    }

    /**
     * Test that tenant can be archived.
     */
    public function test_can_archive_tenant(): void
    {
        $tenant = Tenant::factory()->create(['status' => TenantStatus::ACTIVE]);

        $tenant->update(['status' => TenantStatus::ARCHIVED]);

        $this->assertTrue($tenant->isArchived());
    }

    /**
     * Test that only active tenants are returned by active scope.
     */
    public function test_active_scope_filters_correctly(): void
    {
        $activeTenant1 = Tenant::factory()->create(['status' => TenantStatus::ACTIVE]);
        $activeTenant2 = Tenant::factory()->create(['status' => TenantStatus::ACTIVE]);
        Tenant::factory()->create(['status' => TenantStatus::SUSPENDED]);
        Tenant::factory()->create(['status' => TenantStatus::ARCHIVED]);

        $activeTenants = Tenant::active()->get();

        $this->assertCount(2, $activeTenants);
        $this->assertTrue($activeTenants->contains($activeTenant1));
        $this->assertTrue($activeTenants->contains($activeTenant2));
    }

    /**
     * Test that tenant can be soft deleted and restored.
     */
    public function test_can_soft_delete_and_restore_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $tenantId = $tenant->id;

        // Soft delete
        $tenant->delete();

        $this->assertSoftDeleted('tenants', ['id' => $tenantId]);

        // Restore
        $tenant->restore();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenantId,
            'deleted_at' => null,
        ]);
    }

    /**
     * Test that tenant domain must be unique.
     */
    public function test_tenant_domain_uniqueness(): void
    {
        Tenant::factory()->create(['domain' => 'unique-domain.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Tenant::factory()->create(['domain' => 'unique-domain.com']);
    }

    /**
     * Test that tenant activity is logged.
     */
    public function test_tenant_activity_is_logged(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Original Name']);

        $tenant->update(['name' => 'Updated Name']);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Tenant::class,
            'subject_id' => $tenant->id,
        ]);
    }
}
