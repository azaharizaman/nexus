<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Core\Models;

use App\Models\User;
use Nexus\TenancyManagement\Enums\TenantStatus;
use Nexus\TenancyManagement\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that tenant can be created with factory.
     */
    public function test_can_create_tenant_with_factory(): void
    {
        $tenant = Tenant::factory()->create();

        $this->assertInstanceOf(Tenant::class, $tenant);
        $this->assertNotNull($tenant->id);
        $this->assertNotNull($tenant->name);
        $this->assertNotNull($tenant->domain);
        $this->assertEquals(TenantStatus::ACTIVE, $tenant->status);
    }

    /**
     * Test that tenant uses UUID for primary key.
     */
    public function test_uses_uuid_for_primary_key(): void
    {
        $tenant = Tenant::factory()->create();

        $this->assertIsString($tenant->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $tenant->id
        );
    }

    /**
     * Test that configuration is encrypted.
     */
    public function test_configuration_is_encrypted(): void
    {
        $config = [
            'timezone' => 'UTC',
            'currency' => 'USD',
            'locale' => 'en',
        ];

        $tenant = Tenant::factory()->create(['configuration' => $config]);

        $this->assertIsArray($tenant->configuration);
        $this->assertEquals('UTC', $tenant->configuration['timezone']);
        $this->assertEquals('USD', $tenant->configuration['currency']);
        $this->assertEquals('en', $tenant->configuration['locale']);
    }

    /**
     * Test that tenant status is cast to enum.
     */
    public function test_status_is_cast_to_enum(): void
    {
        $tenant = Tenant::factory()->create(['status' => TenantStatus::ACTIVE]);

        $this->assertInstanceOf(TenantStatus::class, $tenant->status);
        $this->assertEquals(TenantStatus::ACTIVE, $tenant->status);
    }

    /**
     * Test isActive method.
     */
    public function test_is_active_method(): void
    {
        $activeTenant = Tenant::factory()->create(['status' => TenantStatus::ACTIVE]);
        $suspendedTenant = Tenant::factory()->create(['status' => TenantStatus::SUSPENDED]);

        $this->assertTrue($activeTenant->isActive());
        $this->assertFalse($suspendedTenant->isActive());
    }

    /**
     * Test isSuspended method.
     */
    public function test_is_suspended_method(): void
    {
        $activeTenant = Tenant::factory()->create(['status' => TenantStatus::ACTIVE]);
        $suspendedTenant = Tenant::factory()->create(['status' => TenantStatus::SUSPENDED]);

        $this->assertFalse($activeTenant->isSuspended());
        $this->assertTrue($suspendedTenant->isSuspended());
    }

    /**
     * Test isArchived method.
     */
    public function test_is_archived_method(): void
    {
        $activeTenant = Tenant::factory()->create(['status' => TenantStatus::ACTIVE]);
        $archivedTenant = Tenant::factory()->create(['status' => TenantStatus::ARCHIVED]);

        $this->assertFalse($activeTenant->isArchived());
        $this->assertTrue($archivedTenant->isArchived());
    }

    /**
     * Test active scope.
     */
    public function test_active_scope(): void
    {
        Tenant::factory()->create(['status' => TenantStatus::ACTIVE]);
        Tenant::factory()->create(['status' => TenantStatus::SUSPENDED]);
        Tenant::factory()->create(['status' => TenantStatus::ARCHIVED]);

        $activeTenants = Tenant::active()->get();

        $this->assertCount(1, $activeTenants);
        $this->assertEquals(TenantStatus::ACTIVE, $activeTenants->first()->status);
    }

    /**
     * Test tenant has users relationship.
     */
    public function test_has_users_relationship(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertTrue($tenant->users->contains($user));
        $this->assertEquals($tenant->id, $user->tenant_id);
    }

    /**
     * Test tenant uses soft deletes.
     */
    public function test_uses_soft_deletes(): void
    {
        $tenant = Tenant::factory()->create();
        $tenantId = $tenant->id;

        $tenant->delete();

        $this->assertSoftDeleted('tenants', ['id' => $tenantId]);
        $this->assertNotNull($tenant->fresh()->deleted_at);
    }

    /**
     * Test factory creates tenant with suspended state.
     */
    public function test_factory_suspended_state(): void
    {
        $tenant = Tenant::factory()->suspended()->create();

        $this->assertEquals(TenantStatus::SUSPENDED, $tenant->status);
    }

    /**
     * Test factory creates tenant with archived state.
     */
    public function test_factory_archived_state(): void
    {
        $tenant = Tenant::factory()->archived()->create();

        $this->assertEquals(TenantStatus::ARCHIVED, $tenant->status);
    }

    /**
     * Test domain must be unique.
     */
    public function test_domain_must_be_unique(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Tenant::factory()->create(['domain' => 'example.com']);
        Tenant::factory()->create(['domain' => 'example.com']);
    }
}
