<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Models\User;
use Nexus\TenancyManagement\Enums\TenantStatus;
use Nexus\TenancyManagement\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $normalUser;

    protected Tenant $sharedTenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a shared tenant for test users
        $this->sharedTenant = Tenant::factory()->create();

        // Create admin user with the shared tenant
        $this->adminUser = User::factory()->admin()->create([
            'tenant_id' => $this->sharedTenant->id,
        ]);

        // Create normal user with the shared tenant
        $this->normalUser = User::factory()->create([
            'tenant_id' => $this->sharedTenant->id,
        ]);
    }

    /**
     * Test that unauthenticated users cannot access tenant endpoints.
     */
    public function test_unauthenticated_users_cannot_access_tenants(): void
    {
        $response = $this->getJson('/api/v1/tenants');

        $response->assertUnauthorized();
    }

    /**
     * Test that non-admin users cannot list tenants.
     */
    public function test_non_admin_users_cannot_list_tenants(): void
    {
        Sanctum::actingAs($this->normalUser);

        $response = $this->getJson('/api/v1/tenants');

        $response->assertForbidden();
    }

    /**
     * Test that admin users can list tenants.
     */
    public function test_admin_can_list_tenants(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        Tenant::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/tenants');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'domain',
                        'status',
                        'status_label',
                        'contact_email',
                        'created_at',
                        'updated_at',
                        'links',
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(4, 'data'); // 3 created + 1 shared from setUp
    }

    /**
     * Test pagination on tenant list.
     */
    public function test_tenant_list_supports_pagination(): void
    {
        Sanctum::actingAs($this->adminUser);

        Tenant::factory()->count(20)->create();

        $response = $this->getJson('/api/v1/tenants?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    /**
     * Test filtering tenants by status.
     */
    public function test_can_filter_tenants_by_status(): void
    {
        Sanctum::actingAs($this->adminUser);

        Tenant::factory()->create(['status' => TenantStatus::ACTIVE]);
        Tenant::factory()->create(['status' => TenantStatus::ACTIVE]);
        Tenant::factory()->create(['status' => TenantStatus::SUSPENDED]);

        $response = $this->getJson('/api/v1/tenants?status=active');

        $response->assertOk()
            ->assertJsonCount(3, 'data'); // 2 created + 1 shared from setUp (which is active)
    }

    /**
     * Test searching tenants by name or domain.
     */
    public function test_can_search_tenants(): void
    {
        Sanctum::actingAs($this->adminUser);

        Tenant::factory()->create(['name' => 'Acme Corporation', 'domain' => 'acme.com']);
        Tenant::factory()->create(['name' => 'Tech Startup', 'domain' => 'techstartup.com']);

        $response = $this->getJson('/api/v1/tenants?search=Acme');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Acme Corporation');
    }

    /**
     * Test sorting tenants.
     */
    public function test_can_sort_tenants(): void
    {
        Sanctum::actingAs($this->adminUser);

        Tenant::factory()->create(['name' => 'Zeta Corp']);
        Tenant::factory()->create(['name' => 'Alpha Inc']);
        Tenant::factory()->create(['name' => 'Beta LLC']);

        $response = $this->getJson('/api/v1/tenants?sort_by=name&sort_direction=asc');

        $response->assertOk();

        // Get all tenant names and verify sorting
        $data = $response->json('data');
        $names = array_column($data, 'name');
        $sortedNames = $names;
        sort($sortedNames);

        $this->assertEquals($sortedNames, $names, 'Tenants should be sorted by name in ascending order');
    }

    /**
     * Test admin can create a tenant.
     */
    public function test_admin_can_create_tenant(): void
    {
        Sanctum::actingAs($this->adminUser);

        $tenantData = [
            'name' => 'New Company',
            'domain' => 'newcompany.com',
            'contact_email' => 'contact@newcompany.com',
            'contact_name' => 'John Doe',
            'contact_phone' => '+1-555-1234',
            'subscription_plan' => 'enterprise',
        ];

        $response = $this->postJson('/api/v1/tenants', $tenantData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'domain',
                    'status',
                    'contact_email',
                ],
            ])
            ->assertJsonPath('data.name', 'New Company')
            ->assertJsonPath('data.domain', 'newcompany.com');

        $this->assertDatabaseHas('tenants', [
            'name' => 'New Company',
            'domain' => 'newcompany.com',
        ]);
    }

    /**
     * Test non-admin cannot create a tenant.
     */
    public function test_non_admin_cannot_create_tenant(): void
    {
        Sanctum::actingAs($this->normalUser);

        $tenantData = [
            'name' => 'New Company',
            'domain' => 'newcompany.com',
            'contact_email' => 'contact@newcompany.com',
        ];

        $response = $this->postJson('/api/v1/tenants', $tenantData);

        $response->assertForbidden();
    }

    /**
     * Test validation on tenant creation.
     */
    public function test_validates_required_fields_on_create(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson('/api/v1/tenants', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'domain', 'contact_email']);
    }

    /**
     * Test domain uniqueness validation on create.
     */
    public function test_validates_domain_uniqueness_on_create(): void
    {
        Sanctum::actingAs($this->adminUser);

        Tenant::factory()->create(['domain' => 'existing.com']);

        $response = $this->postJson('/api/v1/tenants', [
            'name' => 'New Company',
            'domain' => 'existing.com',
            'contact_email' => 'contact@newcompany.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['domain']);
    }

    /**
     * Test admin can view a single tenant.
     */
    public function test_admin_can_view_tenant(): void
    {
        Sanctum::actingAs($this->adminUser);

        $tenant = Tenant::factory()->create([
            'name' => 'Test Company',
            'domain' => 'test.com',
        ]);

        $response = $this->getJson("/api/v1/tenants/{$tenant->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $tenant->id)
            ->assertJsonPath('data.name', 'Test Company')
            ->assertJsonPath('data.domain', 'test.com');
    }

    /**
     * Test non-admin cannot view a tenant.
     */
    public function test_non_admin_cannot_view_tenant(): void
    {
        Sanctum::actingAs($this->normalUser);

        $tenant = Tenant::factory()->create();

        $response = $this->getJson("/api/v1/tenants/{$tenant->id}");

        $response->assertForbidden();
    }

    /**
     * Test returns 404 for non-existent tenant.
     */
    public function test_returns_404_for_non_existent_tenant(): void
    {
        Sanctum::actingAs($this->adminUser);

        $nonExistentId = '00000000-0000-0000-0000-000000000000';

        $response = $this->getJson("/api/v1/tenants/{$nonExistentId}");

        $response->assertNotFound();
    }

    /**
     * Test admin can update a tenant.
     */
    public function test_admin_can_update_tenant(): void
    {
        Sanctum::actingAs($this->adminUser);

        $tenant = Tenant::factory()->create([
            'name' => 'Old Name',
            'status' => TenantStatus::ACTIVE,
        ]);

        $updateData = [
            'name' => 'New Name',
            'status' => TenantStatus::SUSPENDED->value,
        ];

        $response = $this->patchJson("/api/v1/tenants/{$tenant->id}", $updateData);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.status', 'suspended');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'New Name',
            'status' => TenantStatus::SUSPENDED->value,
        ]);
    }

    /**
     * Test non-admin cannot update a tenant.
     */
    public function test_non_admin_cannot_update_tenant(): void
    {
        Sanctum::actingAs($this->normalUser);

        $tenant = Tenant::factory()->create(['name' => 'Original Name']);

        $response = $this->patchJson("/api/v1/tenants/{$tenant->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertForbidden();
    }

    /**
     * Test validates domain uniqueness on update.
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
     * Test allows updating tenant to same domain.
     */
    public function test_allows_updating_tenant_to_same_domain(): void
    {
        Sanctum::actingAs($this->adminUser);

        $tenant = Tenant::factory()->create(['domain' => 'same.com']);

        $response = $this->patchJson("/api/v1/tenants/{$tenant->id}", [
            'domain' => 'same.com',
            'name' => 'Updated Name',
        ]);

        $response->assertOk();
    }

    /**
     * Test admin can archive (delete) a tenant.
     */
    public function test_admin_can_archive_tenant(): void
    {
        Sanctum::actingAs($this->adminUser);

        $tenant = Tenant::factory()->create(['status' => TenantStatus::ACTIVE]);

        $response = $this->deleteJson("/api/v1/tenants/{$tenant->id}");

        $response->assertOk()
            ->assertJson(['message' => 'Tenant archived successfully']);

        $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);

        // Verify status was updated to ARCHIVED
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => TenantStatus::ARCHIVED->value,
        ]);
    }

    /**
     * Test non-admin cannot archive a tenant.
     */
    public function test_non_admin_cannot_archive_tenant(): void
    {
        Sanctum::actingAs($this->normalUser);

        $tenant = Tenant::factory()->create();

        $response = $this->deleteJson("/api/v1/tenants/{$tenant->id}");

        $response->assertForbidden();
    }

    /**
     * Test can include users count with tenants.
     */
    public function test_can_include_users_count(): void
    {
        Sanctum::actingAs($this->adminUser);

        $tenant = Tenant::factory()->create();
        User::factory()->count(5)->create(['tenant_id' => $tenant->id]);

        $response = $this->getJson("/api/v1/tenants/{$tenant->id}?with_users_count=1");

        $response->assertOk()
            ->assertJsonPath('data.users_count', 5);
    }

    /**
     * Test can include archived tenants in list.
     */
    public function test_can_include_archived_tenants(): void
    {
        Sanctum::actingAs($this->adminUser);

        $activeTenant = Tenant::factory()->create(['status' => TenantStatus::ACTIVE]);
        $archivedTenant = Tenant::factory()->create(['status' => TenantStatus::ARCHIVED]);
        $archivedTenant->delete();

        // Without archived - should include shared tenant and activeTenant
        $response = $this->getJson('/api/v1/tenants');
        $response->assertOk()->assertJsonCount(2, 'data'); // shared + active

        // With archived - should include all three
        $response = $this->getJson('/api/v1/tenants?with_archived=1');
        $response->assertOk()->assertJsonCount(3, 'data'); // shared + active + archived
    }

    /**
     * Test configuration is only visible to admins.
     */
    public function test_configuration_is_only_visible_to_admins(): void
    {
        Sanctum::actingAs($this->adminUser);

        $tenant = Tenant::factory()->create([
            'configuration' => ['secret' => 'value'],
        ]);

        $response = $this->getJson("/api/v1/tenants/{$tenant->id}");

        $response->assertOk()
            ->assertJsonPath('data.configuration.secret', 'value');
    }
}
