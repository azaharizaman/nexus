<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Helpers;

use Nexus\TenancyManagement\Contracts\TenantManagerContract;
use Nexus\TenancyManagement\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantHelperTest extends TestCase
{
    use RefreshDatabase;

    protected TenantManagerContract $tenantManager;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantManager = app(TenantManagerContract::class);
    }

    /**
     * Test tenant() helper returns current tenant
     */
    public function test_tenant_helper_returns_current_tenant(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Helper Test Tenant']);

        $this->tenantManager->setActive($tenant);

        $result = tenant();

        $this->assertNotNull($result);
        $this->assertInstanceOf(Tenant::class, $result);
        $this->assertEquals($tenant->id, $result->id);
        $this->assertEquals($tenant->name, $result->name);
    }

    /**
     * Test tenant() helper returns null when no tenant is set
     */
    public function test_tenant_helper_returns_null_when_no_tenant_set(): void
    {
        $result = tenant();

        $this->assertNull($result);
    }

    /**
     * Test tenant() helper reflects tenant changes
     */
    public function test_tenant_helper_reflects_tenant_changes(): void
    {
        $tenant1 = Tenant::factory()->create(['name' => 'Tenant 1']);
        $tenant2 = Tenant::factory()->create(['name' => 'Tenant 2']);

        // Set first tenant
        $this->tenantManager->setActive($tenant1);
        $this->assertEquals($tenant1->id, tenant()?->id);

        // Change to second tenant
        $this->tenantManager->setActive($tenant2);
        $this->assertEquals($tenant2->id, tenant()?->id);
    }

    /**
     * Test tenant() helper can access tenant properties
     */
    public function test_tenant_helper_can_access_tenant_properties(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Property Test Tenant',
            'domain' => 'property-test.example.com',
            'billing_email' => 'billing@property-test.com',
        ]);

        $this->tenantManager->setActive($tenant);

        $result = tenant();

        $this->assertNotNull($result);
        $this->assertEquals('Property Test Tenant', $result->name);
        $this->assertEquals('property-test.example.com', $result->domain);
        $this->assertEquals('billing@property-test.com', $result->billing_email);
    }

    /**
     * Test tenant() helper is consistent across multiple calls
     */
    public function test_tenant_helper_is_consistent(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Consistent Tenant']);
        $this->tenantManager->setActive($tenant);

        // Multiple calls should return the same tenant
        $result1 = tenant();
        $result2 = tenant();
        $result3 = tenant();

        $this->assertNotNull($result1);
        $this->assertEquals($result1->id, $result2->id);
        $this->assertEquals($result2->id, $result3->id);
    }
}
