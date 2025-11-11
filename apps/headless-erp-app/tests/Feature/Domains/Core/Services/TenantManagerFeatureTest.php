<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Core\Services;

use Azaharizaman\Erp\Core\Contracts\TenantManagerContract;
use Azaharizaman\Erp\Core\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class TenantManagerFeatureTest extends TestCase
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
     * Test that service is bound correctly
     */
    public function test_service_is_bound_in_container(): void
    {
        $service = app(TenantManagerContract::class);

        $this->assertInstanceOf(TenantManagerContract::class, $service);
    }

    /**
     * Test creating tenant logs activity
     */
    public function test_creating_tenant_logs_activity(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $data = [
            'name' => 'Activity Log Test Tenant',
            'domain' => 'activity-log-test.example.com',
            'billing_email' => 'billing@activity-log-test.com',
        ];

        $tenant = $this->tenantManager->create($data);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Tenant::class,
            'subject_id' => $tenant->id,
            'description' => 'Tenant created',
            'causer_id' => $user->id,
        ]);
    }

    /**
     * Test impersonation creates audit log
     */
    public function test_impersonation_creates_audit_log(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Grant impersonation permission
        \Illuminate\Support\Facades\Gate::define('impersonate-tenant', fn () => true);

        $originalTenant = Tenant::factory()->create(['name' => 'Original']);
        $targetTenant = Tenant::factory()->create(['name' => 'Target']);

        $this->tenantManager->setActive($originalTenant);
        $this->tenantManager->impersonate($targetTenant, 'Testing impersonation audit');

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Tenant::class,
            'subject_id' => $targetTenant->id,
            'description' => 'Tenant impersonation started',
            'causer_id' => $user->id,
        ]);

        $activity = Activity::where('subject_id', $targetTenant->id)
            ->where('description', 'Tenant impersonation started')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('Testing impersonation audit', $activity->properties['reason']);
        $this->assertEquals($originalTenant->id, $activity->properties['original_tenant_id']);
    }

    /**
     * Test stopping impersonation logs activity
     */
    public function test_stopping_impersonation_logs_activity(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Grant impersonation permission
        \Illuminate\Support\Facades\Gate::define('impersonate-tenant', fn () => true);

        $tenant = Tenant::factory()->create();

        $this->tenantManager->impersonate($tenant, 'Test stop impersonation');
        $this->tenantManager->stopImpersonation();

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Tenant::class,
            'subject_id' => $tenant->id,
            'description' => 'Tenant impersonation stopped',
            'causer_id' => $user->id,
        ]);

        $activity = Activity::where('subject_id', $tenant->id)
            ->where('description', 'Tenant impersonation stopped')
            ->first();

        $this->assertNotNull($activity);
        $this->assertNotNull($activity->properties['duration']);
    }

    /**
     * Test context persistence across multiple operations
     */
    public function test_context_persists_across_operations(): void
    {
        $tenant1 = Tenant::factory()->create(['name' => 'Tenant 1']);
        $tenant2 = Tenant::factory()->create(['name' => 'Tenant 2']);

        // Set initial tenant
        $this->tenantManager->setActive($tenant1);
        $this->assertEquals($tenant1->id, $this->tenantManager->current()->id);

        // Context should persist
        $this->assertEquals($tenant1->id, $this->tenantManager->current()->id);

        // Switch to another tenant
        $this->tenantManager->setActive($tenant2);
        $this->assertEquals($tenant2->id, $this->tenantManager->current()->id);

        // New context should persist
        $this->assertEquals($tenant2->id, $this->tenantManager->current()->id);
    }

    /**
     * Test complete impersonation workflow
     */
    public function test_complete_impersonation_workflow(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Grant impersonation permission
        \Illuminate\Support\Facades\Gate::define('impersonate-tenant', fn () => true);

        $tenant1 = Tenant::factory()->create(['name' => 'User Tenant']);
        $tenant2 = Tenant::factory()->create(['name' => 'Support Target']);

        // User starts with their tenant
        $this->tenantManager->setActive($tenant1);
        $this->assertEquals($tenant1->id, $this->tenantManager->current()->id);

        // Support staff impersonates another tenant
        $this->tenantManager->impersonate($tenant2, 'Customer support ticket #123');
        $this->assertEquals($tenant2->id, $this->tenantManager->current()->id);

        // After stopping impersonation, original tenant is restored
        $this->tenantManager->stopImpersonation();
        $this->assertEquals($tenant1->id, $this->tenantManager->current()->id);

        // Verify audit trail
        $startActivity = Activity::where('description', 'Tenant impersonation started')->first();
        $stopActivity = Activity::where('description', 'Tenant impersonation stopped')->first();

        $this->assertNotNull($startActivity);
        $this->assertNotNull($stopActivity);
        $this->assertEquals('Customer support ticket #123', $startActivity->properties['reason']);
    }

    /**
     * Test tenant creation with configuration
     */
    public function test_tenant_creation_with_configuration(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $data = [
            'name' => 'Configured Tenant',
            'domain' => 'configured.example.com',
            'billing_email' => 'billing@configured.com',
            'configuration' => [
                'timezone' => 'UTC',
                'locale' => 'en_US',
                'features' => ['inventory', 'sales', 'purchasing'],
            ],
        ];

        $tenant = $this->tenantManager->create($data);

        $this->assertNotNull($tenant->configuration);
        $this->assertEquals('UTC', $tenant->configuration['timezone']);
        $this->assertEquals('en_US', $tenant->configuration['locale']);
        $this->assertIsArray($tenant->configuration['features']);
        $this->assertContains('inventory', $tenant->configuration['features']);
    }

    /**
     * Test performance constraint - context resolution under 10ms
     */
    public function test_context_resolution_performance(): void
    {
        $tenant = Tenant::factory()->create();
        $this->tenantManager->setActive($tenant);

        // Test that each individual context resolution is under 10ms
        $maxAllowedMs = 10;
        $maxObservedMs = 0;

        for ($i = 0; $i < 100; $i++) {
            $start = microtime(true);
            $this->tenantManager->current();
            $end = microtime(true);
            $durationMs = ($end - $start) * 1000;

            if ($durationMs > $maxObservedMs) {
                $maxObservedMs = $durationMs;
            }

            $this->assertLessThan(
                $maxAllowedMs,
                $durationMs,
                "Context resolution iteration {$i} took {$durationMs}ms, should be under {$maxAllowedMs}ms"
            );
        }
    }

    /**
     * Test that tenant manager resolves from container consistently
     */
    public function test_tenant_manager_singleton_behavior(): void
    {
        $service1 = app(TenantManagerContract::class);
        $service2 = app(TenantManagerContract::class);

        $this->assertSame($service1, $service2);

        $tenant = Tenant::factory()->create();
        $service1->setActive($tenant);

        // Both instances should see the same tenant
        $this->assertEquals($tenant->id, $service2->current()->id);
    }
}
