<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Azaharizaman\Erp\Core\Contracts\TenantManagerContract;
use Azaharizaman\Erp\Core\Enums\TenantStatus;
use Azaharizaman\Erp\Core\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Unit tests for TenantManager service
 *
 * This test suite validates:
 * - Tenant creation with validation
 * - Active tenant management (setActive/current)
 * - Tenant impersonation for support operations
 * - Audit logging of impersonation activities
 * - Authorization checks
 */
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
     * TASK-076: Test setActive() method sets current tenant
     */
    public function test_set_active_sets_current_tenant(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Test Tenant']);

        $this->tenantManager->setActive($tenant);

        $currentTenant = $this->tenantManager->current();

        $this->assertNotNull($currentTenant);
        $this->assertEquals($tenant->id, $currentTenant->id);
        $this->assertEquals('Test Tenant', $currentTenant->name);
    }

    /**
     * TASK-076: Test current() method returns active tenant
     */
    public function test_current_returns_active_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $this->tenantManager->setActive($tenant);

        $current = $this->tenantManager->current();

        $this->assertInstanceOf(Tenant::class, $current);
        $this->assertEquals($tenant->id, $current->id);
    }

    /**
     * TASK-076: Test current() returns null when no tenant is set
     */
    public function test_current_returns_null_when_not_set(): void
    {
        $current = $this->tenantManager->current();

        $this->assertNull($current);
    }

    /**
     * TASK-076: Test can switch between multiple tenants
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

    /**
     * TASK-077: Test impersonation functionality
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
        $this->assertEquals('Target Tenant', $current->name);
    }

    /**
     * TASK-077: Test impersonation without original tenant
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
     * TASK-077: Test stopping impersonation restores original tenant
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

        // Verify we're impersonating
        $this->assertEquals($targetTenant->id, $this->tenantManager->current()->id);

        $this->tenantManager->stopImpersonation();

        // Verify we're back to original tenant
        $current = $this->tenantManager->current();
        $this->assertNotNull($current);
        $this->assertEquals($originalTenant->id, $current->id);
    }

    /**
     * TASK-077: Test stopping impersonation clears tenant when no original
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
     * TASK-077: Test impersonation fails without authentication
     */
    public function test_impersonation_fails_without_authentication(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Impersonation requires an authenticated user');

        $tenant = Tenant::factory()->create();
        $this->tenantManager->impersonate($tenant, 'Should fail');
    }

    /**
     * TASK-077: Test impersonation fails without authorization
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
     * TASK-077: Test impersonation logs activity for audit
     */
    public function test_impersonation_logs_activity_for_audit(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Grant impersonation permission
        \Illuminate\Support\Facades\Gate::define('impersonate-tenant', fn () => true);

        $tenant = Tenant::factory()->create();

        $this->tenantManager->impersonate($tenant, 'Security audit test');

        // Verify activity was logged
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Tenant::class,
            'subject_id' => $tenant->id,
            'description' => 'Tenant impersonation started',
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);
    }

    /**
     * TASK-077: Test stopping impersonation logs activity
     */
    public function test_stop_impersonation_logs_activity(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Grant impersonation permission
        \Illuminate\Support\Facades\Gate::define('impersonate-tenant', fn () => true);

        $tenant = Tenant::factory()->create();

        $this->tenantManager->impersonate($tenant, 'Test impersonation');
        $this->tenantManager->stopImpersonation();

        // Verify stop activity was logged
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Tenant::class,
            'subject_id' => $tenant->id,
            'description' => 'Tenant impersonation stopped',
        ]);
    }

    /**
     * Test TenantManager can create tenant with valid data
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
    }

    /**
     * Test TenantManager creates tenant with minimal data
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
     * Test TenantManager accepts any subscription plan value
     *
     * Subscription plans are not restricted to a predefined set of values.
     * This test verifies that any string value is accepted for subscription_plan.
     */
    public function test_accepts_any_subscription_plan_value(): void
    {
        $testPlans = ['premium', 'enterprise', 'starter', 'custom-plan', 'trial-2024'];

        foreach ($testPlans as $plan) {
            $data = [
                'name' => "Tenant with {$plan}",
                'domain' => strtolower($plan).'.example.com',
                'billing_email' => "billing@{$plan}.com",
                'subscription_plan' => $plan,
            ];

            $tenant = $this->tenantManager->create($data);

            $this->assertInstanceOf(Tenant::class, $tenant);
            $this->assertEquals($plan, $tenant->subscription_plan);
        }
    }

    /**
     * Test TenantManager validates required fields
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
     * Test TenantManager validates duplicate domain
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
     * Test TenantManager validates email format
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
     * Test TenantManager implements contract
     */
    public function test_implements_tenant_manager_contract(): void
    {
        $this->assertInstanceOf(TenantManagerContract::class, $this->tenantManager);
    }

    /**
     * Test tenant creation is logged
     */
    public function test_tenant_creation_is_logged(): void
    {
        $data = [
            'name' => 'Logged Tenant',
            'domain' => 'logged.example.com',
            'billing_email' => 'billing@logged.com',
        ];

        $tenant = $this->tenantManager->create($data);

        // Verify creation was logged
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Tenant::class,
            'subject_id' => $tenant->id,
            'description' => 'Tenant created',
        ]);
    }
}
