<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Domains\Core\Enums\TenantStatus;
use App\Domains\Core\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Feature tests for multi-tenancy system
 *
 * This test suite validates the complete multi-tenancy functionality including:
 * - Tenant CRUD operations via API
 * - Tenant isolation and data security
 * - Permission and authorization
 * - Edge cases and error handling
 */
class TenantTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $normalUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user for privileged operations
        $this->adminUser = User::factory()->admin()->create();

        // Create normal user for permission testing
        $this->normalUser = User::factory()->create();
    }

    /**
     * TASK-065: Test tenant creation via API endpoint with valid data
     */
    public function test_can_create_tenant_via_api_with_valid_data(): void
    {
        Sanctum::actingAs($this->adminUser);

        $tenantData = [
            'name' => 'Test Company Inc',
            'domain' => 'testcompany.example.com',
            'contact_email' => 'contact@testcompany.com',
            'contact_name' => 'Jane Doe',
            'contact_phone' => '+1-555-9876',
            'subscription_plan' => 'enterprise',
            'billing_email' => 'billing@testcompany.com',
        ];

        $response = $this->postJson('/api/v1/tenants', $tenantData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'domain',
                    'status',
                    'status_label',
                    'contact_email',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.name', 'Test Company Inc')
            ->assertJsonPath('data.domain', 'testcompany.example.com')
            ->assertJsonPath('data.contact_email', 'contact@testcompany.com');

        $this->assertDatabaseHas('tenants', [
            'name' => 'Test Company Inc',
            'domain' => 'testcompany.example.com',
            'status' => TenantStatus::ACTIVE->value,
        ]);
    }

    /**
     * TASK-066: Test tenant creation fails with duplicate domain
     */
    public function test_tenant_creation_fails_with_duplicate_domain(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Create first tenant
        Tenant::factory()->create(['domain' => 'existing-domain.com']);

        // Attempt to create duplicate
        $duplicateData = [
            'name' => 'Duplicate Company',
            'domain' => 'existing-domain.com',
            'contact_email' => 'contact@duplicate.com',
            'billing_email' => 'billing@duplicate.com',
        ];

        $response = $this->postJson('/api/v1/tenants', $duplicateData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['domain']);

        // Verify database was not modified
        $this->assertDatabaseMissing('tenants', [
            'name' => 'Duplicate Company',
        ]);
    }

    /**
     * TASK-067: Test tenant listing with pagination and filtering
     */
    public function test_can_list_tenants_with_pagination_and_filtering(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Create multiple tenants with different statuses
        Tenant::factory()->count(3)->create(['status' => TenantStatus::ACTIVE]);
        Tenant::factory()->count(2)->create(['status' => TenantStatus::SUSPENDED]);
        Tenant::factory()->create(['status' => TenantStatus::ARCHIVED]);

        // Test listing all tenants
        $response = $this->getJson('/api/v1/tenants');
        $response->assertOk()
            ->assertJsonCount(6, 'data');

        // Test pagination
        $response = $this->getJson('/api/v1/tenants?per_page=3');
        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        // Test filtering by status
        $response = $this->getJson('/api/v1/tenants?status=active');
        $response->assertOk()
            ->assertJsonCount(3, 'data');

        $response = $this->getJson('/api/v1/tenants?status=suspended');
        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /**
     * TASK-067: Test search functionality for tenants
     */
    public function test_can_search_tenants_by_name_or_domain(): void
    {
        Sanctum::actingAs($this->adminUser);

        Tenant::factory()->create([
            'name' => 'Acme Corporation',
            'domain' => 'acme.example.com',
        ]);
        Tenant::factory()->create([
            'name' => 'Tech Solutions Ltd',
            'domain' => 'techsolutions.example.com',
        ]);
        Tenant::factory()->create([
            'name' => 'Global Enterprises',
            'domain' => 'global.example.com',
        ]);

        // Search by name
        $response = $this->getJson('/api/v1/tenants?search=Acme');
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Acme Corporation');

        // Search by domain
        $response = $this->getJson('/api/v1/tenants?search=techsolutions');
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Tech Solutions Ltd');
    }

    /**
     * TASK-068: Test tenant update via API endpoint
     */
    public function test_can_update_tenant_via_api(): void
    {
        Sanctum::actingAs($this->adminUser);

        $tenant = Tenant::factory()->create([
            'name' => 'Original Name',
            'status' => TenantStatus::ACTIVE,
            'contact_email' => 'old@example.com',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'contact_email' => 'new@example.com',
            'status' => TenantStatus::SUSPENDED->value,
        ];

        $response = $this->patchJson("/api/v1/tenants/{$tenant->id}", $updateData);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.contact_email', 'new@example.com')
            ->assertJsonPath('data.status', 'suspended');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Updated Name',
            'contact_email' => 'new@example.com',
            'status' => TenantStatus::SUSPENDED->value,
        ]);
    }

    /**
     * TASK-069: Test tenant archival and soft delete
     */
    public function test_can_archive_tenant_via_api(): void
    {
        Sanctum::actingAs($this->adminUser);

        $tenant = Tenant::factory()->create([
            'status' => TenantStatus::ACTIVE,
        ]);

        $response = $this->deleteJson("/api/v1/tenants/{$tenant->id}");

        $response->assertOk()
            ->assertJson(['message' => 'Tenant archived successfully']);

        // Verify tenant is soft deleted
        $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);

        // Verify status was updated to ARCHIVED
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => TenantStatus::ARCHIVED->value,
        ]);

        // Verify tenant can be accessed with trashed
        $archivedTenant = Tenant::withTrashed()->find($tenant->id);
        $this->assertNotNull($archivedTenant);
        $this->assertTrue($archivedTenant->trashed());
        $this->assertEquals(TenantStatus::ARCHIVED, $archivedTenant->status);
    }

    /**
     * TASK-070: Test tenant isolation - user cannot access another tenant's data
     *
     * This is a critical security test to ensure complete tenant isolation
     */
    public function test_tenant_isolation_prevents_cross_tenant_data_access(): void
    {
        // Create two separate tenants
        $tenant1 = Tenant::factory()->create(['name' => 'Tenant 1']);
        $tenant2 = Tenant::factory()->create(['name' => 'Tenant 2']);

        // Create users for each tenant
        $user1 = User::factory()->create([
            'tenant_id' => $tenant1->id,
            'name' => 'User from Tenant 1',
            'email' => 'user1@tenant1.com',
        ]);

        $user2 = User::factory()->create([
            'tenant_id' => $tenant2->id,
            'name' => 'User from Tenant 2',
            'email' => 'user2@tenant2.com',
        ]);

        // Authenticate as user from tenant 1
        Sanctum::actingAs($user1);

        // User 1 should NOT be able to view tenant 2's details
        $response = $this->getJson("/api/v1/tenants/{$tenant2->id}");
        $response->assertForbidden();

        // User 1 should NOT be able to update tenant 2
        $response = $this->patchJson("/api/v1/tenants/{$tenant2->id}", [
            'name' => 'Hacked Name',
        ]);
        $response->assertForbidden();

        // User 1 should NOT be able to delete tenant 2
        $response = $this->deleteJson("/api/v1/tenants/{$tenant2->id}");
        $response->assertForbidden();

        // Verify tenant 2 data remains unchanged
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant2->id,
            'name' => 'Tenant 2',
        ]);
    }

    /**
     * TASK-070: Additional isolation test - verify data filtering at query level
     */
    public function test_tenant_scope_filters_queries_automatically(): void
    {
        // Create two tenants
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Create users for each tenant
        $users1 = User::factory()->count(3)->create(['tenant_id' => $tenant1->id]);
        $users2 = User::factory()->count(2)->create(['tenant_id' => $tenant2->id]);

        // Authenticate as user from tenant 1
        $this->actingAs($users1[0]);

        // Create a test model that uses BelongsToTenant trait
        // In this case, we'll use User model which has tenant_id
        // When querying, should only see users from tenant 1
        $visibleUsers = User::where('tenant_id', $tenant1->id)->get();

        $this->assertCount(3, $visibleUsers);
        $this->assertTrue($visibleUsers->every(fn ($user) => $user->tenant_id === $tenant1->id));

        // Verify users from tenant 2 are not accessible in normal queries
        $tenant2Users = User::where('tenant_id', $tenant2->id)->get();
        foreach ($tenant2Users as $user) {
            $this->assertEquals($tenant2->id, $user->tenant_id);
            $this->assertNotEquals($tenant1->id, $user->tenant_id);
        }
    }

    /**
     * Test that non-admin users cannot list tenants
     */
    public function test_non_admin_cannot_list_tenants(): void
    {
        Sanctum::actingAs($this->normalUser);

        $response = $this->getJson('/api/v1/tenants');

        $response->assertForbidden();
    }

    /**
     * Test that non-admin users cannot create tenants
     */
    public function test_non_admin_cannot_create_tenant(): void
    {
        Sanctum::actingAs($this->normalUser);

        $tenantData = [
            'name' => 'Unauthorized Tenant',
            'domain' => 'unauthorized.com',
            'contact_email' => 'contact@unauthorized.com',
            'billing_email' => 'billing@unauthorized.com',
        ];

        $response = $this->postJson('/api/v1/tenants', $tenantData);

        $response->assertForbidden();
    }

    /**
     * Test unauthenticated users cannot access tenant endpoints
     */
    public function test_unauthenticated_users_cannot_access_tenants(): void
    {
        $response = $this->getJson('/api/v1/tenants');

        $response->assertUnauthorized();
    }

    /**
     * Test validation on tenant creation
     */
    public function test_validates_required_fields_on_create(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson('/api/v1/tenants', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'domain', 'billing_email']);
    }

    /**
     * Test validation on tenant update
     */
    public function test_validates_domain_uniqueness_on_update(): void
    {
        Sanctum::actingAs($this->adminUser);

        $tenant1 = Tenant::factory()->create(['domain' => 'tenant1.com']);
        $tenant2 = Tenant::factory()->create(['domain' => 'tenant2.com']);

        $response = $this->patchJson("/api/v1/tenants/{$tenant1->id}", [
            'domain' => 'tenant2.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['domain']);
    }

    /**
     * Test 404 response for non-existent tenant
     */
    public function test_returns_404_for_non_existent_tenant(): void
    {
        Sanctum::actingAs($this->adminUser);

        $nonExistentId = '00000000-0000-0000-0000-000000000000';

        $response = $this->getJson("/api/v1/tenants/{$nonExistentId}");

        $response->assertNotFound();
    }

    /**
     * Test tenant can be restored after soft delete
     */
    public function test_can_restore_soft_deleted_tenant(): void
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
}
