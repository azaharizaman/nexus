<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Core\Services;

use App\Models\User;
use Nexus\Erp\Core\Contracts\TenantManagerContract;
use Nexus\Erp\Core\Enums\TenantStatus;
use Nexus\Erp\Core\Models\Tenant;
use Nexus\Erp\Core\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TenantManagerTest extends TestCase
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
     * Test that TenantManager implements contract
     */
    public function test_implements_tenant_manager_contract(): void
    {
        $this->assertInstanceOf(TenantManagerContract::class, $this->tenantManager);
    }

    /**
     * Test creating a tenant with valid data
     */
    public function test_can_create_tenant_with_valid_data(): void
    {
        $data = [
            'name' => 'Test Tenant',
            'domain' => 'test-tenant.example.com',
            'billing_email' => 'billing@test-tenant.com',
            'contact_name' => 'John Doe',
            'contact_email' => 'john@test-tenant.com',
            'contact_phone' => '+1234567890',
            'subscription_plan' => 'premium',
        ];

        $tenant = $this->tenantManager->create($data);

        $this->assertInstanceOf(Tenant::class, $tenant);
        $this->assertEquals('Test Tenant', $tenant->name);
        $this->assertEquals('test-tenant.example.com', $tenant->domain);
        $this->assertEquals('billing@test-tenant.com', $tenant->billing_email);
        $this->assertEquals(TenantStatus::ACTIVE, $tenant->status);
        $this->assertDatabaseHas('tenants', [
            'name' => 'Test Tenant',
            'domain' => 'test-tenant.example.com',
        ]);
    }

    /**
     * Test creating tenant with minimal required data
     */
    public function test_can_create_tenant_with_minimal_data(): void
    {
        $data = [
            'name' => 'Minimal Tenant',
            'domain' => 'minimal.example.com',
            'billing_email' => 'billing@minimal.com',
        ];

        $tenant = $this->tenantManager->create($data);

        $this->assertInstanceOf(Tenant::class, $tenant);
        $this->assertEquals('Minimal Tenant', $tenant->name);
        $this->assertEquals(TenantStatus::ACTIVE, $tenant->status);
    }

    /**
     * Test creating tenant fails with missing required fields
     */
    public function test_create_fails_with_missing_required_fields(): void
    {
        $this->expectException(ValidationException::class);

        $this->tenantManager->create([
            'name' => 'Incomplete Tenant',
            // Missing domain and billing_email
        ]);
    }

    /**
     * Test creating tenant fails with duplicate domain
     */
    public function test_create_fails_with_duplicate_domain(): void
    {
        Tenant::factory()->create(['domain' => 'duplicate.example.com']);

        $this->expectException(ValidationException::class);

        $this->tenantManager->create([
            'name' => 'Duplicate Domain Tenant',
            'domain' => 'duplicate.example.com',
            'billing_email' => 'billing@duplicate.com',
        ]);
    }

    /**
     * Test creating tenant fails with invalid email
     */
    public function test_create_fails_with_invalid_email(): void
    {
        $this->expectException(ValidationException::class);

        $this->tenantManager->create([
            'name' => 'Invalid Email Tenant',
            'domain' => 'invalid-email.example.com',
            'billing_email' => 'not-an-email',
        ]);
    }

    /**
     * Test setting active tenant
     */
    public function test_can_set_active_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $this->tenantManager->setActive($tenant);

        $this->assertNotNull($this->tenantManager->current());
        $this->assertEquals($tenant->id, $this->tenantManager->current()->id);
    }

    /**
     * Test getting current tenant returns null when not set
     */
    public function test_current_returns_null_when_not_set(): void
    {
        $current = $this->tenantManager->current();

        $this->assertNull($current);
    }

    /**
     * Test getting current tenant after setting
     */
    public function test_can_get_current_tenant_after_setting(): void
    {
        $tenant = Tenant::factory()->create();

        $this->tenantManager->setActive($tenant);

        $current = $this->tenantManager->current();
        $this->assertNotNull($current);
        $this->assertEquals($tenant->id, $current->id);
        $this->assertEquals($tenant->name, $current->name);
    }

    /**
     * Test impersonating a tenant
     */
    public function test_can_impersonate_tenant(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Grant impersonation permission
        \Illuminate\Support\Facades\Gate::define('impersonate-tenant', fn () => true);

        $originalTenant = Tenant::factory()->create(['name' => 'Original Tenant']);
        $targetTenant = Tenant::factory()->create(['name' => 'Target Tenant']);

        $this->tenantManager->setActive($originalTenant);
        $this->tenantManager->impersonate($targetTenant, 'Support request #12345');

        $current = $this->tenantManager->current();
        $this->assertNotNull($current);
        $this->assertEquals($targetTenant->id, $current->id);
    }

    /**
     * Test impersonating without original tenant
     */
    public function test_can_impersonate_without_original_tenant(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Grant impersonation permission
        \Illuminate\Support\Facades\Gate::define('impersonate-tenant', fn () => true);

        $targetTenant = Tenant::factory()->create(['name' => 'Target Tenant']);

        $this->tenantManager->impersonate($targetTenant, 'Initial support access');

        $current = $this->tenantManager->current();
        $this->assertNotNull($current);
        $this->assertEquals($targetTenant->id, $current->id);
    }

    /**
     * Test stopping impersonation restores original tenant
     */
    public function test_stop_impersonation_restores_original_tenant(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Grant impersonation permission
        \Illuminate\Support\Facades\Gate::define('impersonate-tenant', fn () => true);

        $originalTenant = Tenant::factory()->create(['name' => 'Original Tenant']);
        $targetTenant = Tenant::factory()->create(['name' => 'Target Tenant']);

        $this->tenantManager->setActive($originalTenant);
        $this->tenantManager->impersonate($targetTenant, 'Support request');
        $this->tenantManager->stopImpersonation();

        $current = $this->tenantManager->current();
        $this->assertNotNull($current);
        $this->assertEquals($originalTenant->id, $current->id);
    }

    /**
     * Test stopping impersonation clears tenant when no original
     */
    public function test_stop_impersonation_clears_tenant_when_no_original(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Grant impersonation permission
        \Illuminate\Support\Facades\Gate::define('impersonate-tenant', fn () => true);

        $targetTenant = Tenant::factory()->create(['name' => 'Target Tenant']);

        $this->tenantManager->impersonate($targetTenant, 'Support access');
        $this->tenantManager->stopImpersonation();

        $current = $this->tenantManager->current();
        $this->assertNull($current);
    }

    /**
     * Test impersonation fails without authentication
     */
    public function test_impersonation_fails_without_authentication(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Impersonation requires an authenticated user');

        $tenant = Tenant::factory()->create();
        $this->tenantManager->impersonate($tenant, 'Should fail');
    }

    /**
     * Test impersonation fails without authorization
     */
    public function test_impersonation_fails_without_authorization(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Deny impersonation permission
        \Illuminate\Support\Facades\Gate::define('impersonate-tenant', fn () => false);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->expectExceptionMessage('Unauthorized to impersonate this tenant');

        $tenant = Tenant::factory()->create();
        $this->tenantManager->impersonate($tenant, 'Should fail');
    }

    /**
     * Test multiple tenant switches
     */
    public function test_can_switch_between_multiple_tenants(): void
    {
        $tenant1 = Tenant::factory()->create(['name' => 'Tenant 1']);
        $tenant2 = Tenant::factory()->create(['name' => 'Tenant 2']);
        $tenant3 = Tenant::factory()->create(['name' => 'Tenant 3']);

        $this->tenantManager->setActive($tenant1);
        $this->assertEquals($tenant1->id, $this->tenantManager->current()->id);

        $this->tenantManager->setActive($tenant2);
        $this->assertEquals($tenant2->id, $this->tenantManager->current()->id);

        $this->tenantManager->setActive($tenant3);
        $this->assertEquals($tenant3->id, $this->tenantManager->current()->id);
    }
}
