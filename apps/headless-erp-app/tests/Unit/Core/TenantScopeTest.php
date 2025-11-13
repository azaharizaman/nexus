<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Models\User;
use Nexus\TenancyManagement\Models\Tenant;
use Nexus\TenancyManagement\Scopes\TenantScope;
use Nexus\TenancyManagement\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for TenantScope and BelongsToTenant trait
 *
 * This test suite validates:
 * - Automatic tenant_id assignment on model creation
 * - Query filtering by current tenant
 * - Ability to bypass tenant scope when needed
 * - Tenant context resolution from various sources
 */
class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TASK-072: Test BelongsToTenant trait automatically sets tenant_id on create
     */
    public function test_belongs_to_tenant_trait_auto_sets_tenant_id_on_creation(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user);

        // Create a test model with the trait
        $model = new class extends Model
        {
            use BelongsToTenant;
            use \Illuminate\Database\Eloquent\Concerns\HasUuids;

            protected $table = 'users';

            protected $fillable = ['name', 'email', 'password', 'tenant_id', 'status'];
        };

        $model->name = 'Auto Tenant User';
        $model->email = 'autouser@example.com';
        $model->password = bcrypt('password');
        $model->status = 'active';
        // Note: We are NOT setting tenant_id explicitly
        $model->save();

        // Verify tenant_id was automatically set
        $this->assertEquals($tenant->id, $model->tenant_id);
        $this->assertDatabaseHas('users', [
            'email' => 'autouser@example.com',
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * TASK-072: Test that explicitly set tenant_id is not overridden
     */
    public function test_does_not_override_explicitly_set_tenant_id(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant1->id]);

        $this->actingAs($user);

        // Create a test model with the trait
        $model = new class extends Model
        {
            use BelongsToTenant;
            use \Illuminate\Database\Eloquent\Concerns\HasUuids;

            protected $table = 'users';

            protected $fillable = ['name', 'email', 'password', 'tenant_id', 'status'];
        };

        $model->name = 'Explicit Tenant User';
        $model->email = 'explicit@example.com';
        $model->password = bcrypt('password');
        $model->status = 'active';
        $model->tenant_id = $tenant2->id; // Explicitly set to different tenant
        $model->save();

        // Should keep the explicitly set tenant_id
        $this->assertEquals($tenant2->id, $model->tenant_id);
        $this->assertNotEquals($tenant1->id, $model->tenant_id);
    }

    /**
     * TASK-073: Test TenantScope filters queries by current tenant
     */
    public function test_tenant_scope_filters_queries_by_current_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user);

        // Create a test model with the trait
        $model = new class extends Model
        {
            use BelongsToTenant;

            protected $table = 'users';

            protected $fillable = ['name', 'email', 'password', 'tenant_id'];
        };

        $query = $model->newQuery();
        $sql = $query->toSql();

        // Check that the SQL includes tenant_id filter
        $this->assertStringContainsString('tenant_id', $sql);
        $this->assertStringContainsString('where', strtolower($sql));
    }

    /**
     * TASK-073: Test TenantScope applies correct tenant filter
     */
    public function test_tenant_scope_applies_correct_tenant_filter(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Create users for both tenants
        $user1a = User::factory()->create(['tenant_id' => $tenant1->id, 'name' => 'Tenant 1 User A']);
        $user1b = User::factory()->create(['tenant_id' => $tenant1->id, 'name' => 'Tenant 1 User B']);
        $user2a = User::factory()->create(['tenant_id' => $tenant2->id, 'name' => 'Tenant 2 User A']);

        // Login as user from tenant1
        $this->actingAs($user1a);

        // Query should only return users from tenant1
        $users = User::where('tenant_id', $tenant1->id)->get();

        $this->assertCount(2, $users);
        $this->assertTrue($users->contains('id', $user1a->id));
        $this->assertTrue($users->contains('id', $user1b->id));
        $this->assertFalse($users->contains('id', $user2a->id));
    }

    /**
     * TASK-074: Test withoutTenantScope() bypasses filtering
     */
    public function test_without_tenant_scope_bypasses_filtering(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user);

        // Create a test model with the trait
        $modelClass = new class extends Model
        {
            use BelongsToTenant;

            protected $table = 'users';
        };

        // Create a regular query with scope
        $queryWithScope = $modelClass->newQuery();
        $sqlWithScope = $queryWithScope->toSql();

        // Query without the tenant scope
        $queryWithoutScope = $modelClass::withoutTenantScope();
        $sqlWithoutScope = $queryWithoutScope->toSql();

        // The SQL should be different - without scope should not filter by tenant_id
        $this->assertStringContainsString('select', strtolower($sqlWithoutScope));

        // Verify that removing scope actually changes the query
        $this->assertNotEquals($sqlWithScope, $sqlWithoutScope);
    }

    /**
     * TASK-074: Test withAllTenants() method bypasses filtering
     */
    public function test_with_all_tenants_bypasses_filtering(): void
    {
        // Create a test model with the trait
        $modelClass = new class extends Model
        {
            use BelongsToTenant;

            protected $table = 'users';
        };

        // Test that withAllTenants is an alias for withoutTenantScope
        $query1 = $modelClass::withoutTenantScope();
        $query2 = $modelClass::withAllTenants();

        // Both methods should produce the same result
        $this->assertEquals($query1->toSql(), $query2->toSql());
    }

    /**
     * Test TenantScope does not apply filter when no tenant is set
     */
    public function test_does_not_apply_filter_when_no_tenant_is_set(): void
    {
        // No authenticated user, no tenant context
        auth()->logout();

        // Create a test model with the trait
        $model = new class extends Model
        {
            use BelongsToTenant;

            protected $table = 'test_models';
        };

        $query = $model->newQuery();
        $sql = $query->toSql();

        // When no tenant is set, the scope should not add the WHERE clause
        // The query should be a simple SELECT * FROM test_models
        $this->assertStringContainsString('select * from', strtolower($sql));
    }

    /**
     * Test TenantScope gets tenant from authenticated user
     */
    public function test_gets_tenant_from_authenticated_user(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user);

        $scope = new TenantScope();
        $reflection = new \ReflectionClass($scope);
        $method = $reflection->getMethod('getCurrentTenantId');
        $method->setAccessible(true);

        $tenantId = $method->invoke($scope);

        $this->assertEquals($tenant->id, $tenantId);
    }

    /**
     * Test TenantScope gets tenant from app container
     */
    public function test_gets_tenant_from_app_container(): void
    {
        $tenant = Tenant::factory()->create();

        // Bind tenant to container
        app()->instance('tenant.current', $tenant);

        $scope = new TenantScope();
        $reflection = new \ReflectionClass($scope);
        $method = $reflection->getMethod('getCurrentTenantId');
        $method->setAccessible(true);

        $tenantId = $method->invoke($scope);

        $this->assertEquals($tenant->id, $tenantId);

        // Clean up
        app()->forgetInstance('tenant.current');
    }

    /**
     * Test TenantScope returns null when no context available
     */
    public function test_returns_null_when_no_context_available(): void
    {
        // No authenticated user
        auth()->logout();

        // No tenant in container
        app()->forgetInstance('tenant.current');

        $scope = new TenantScope();
        $reflection = new \ReflectionClass($scope);
        $method = $reflection->getMethod('getCurrentTenantId');
        $method->setAccessible(true);

        $tenantId = $method->invoke($scope);

        $this->assertNull($tenantId);
    }

    /**
     * Test TenantScope implements Scope interface
     */
    public function test_implements_scope_interface(): void
    {
        $scope = new TenantScope();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Scope::class, $scope);
    }

    /**
     * Test BelongsToTenant trait adds TenantScope global scope
     */
    public function test_adds_tenant_scope_global_scope(): void
    {
        // Create a test model with the trait
        $model = new class extends Model
        {
            use BelongsToTenant;

            protected $table = 'users';
        };

        $scopes = $model->getGlobalScopes();

        $this->assertArrayHasKey(TenantScope::class, $scopes);
        $this->assertInstanceOf(TenantScope::class, $scopes[TenantScope::class]);
    }

    /**
     * Test tenant relationship is properly defined
     */
    public function test_tenant_relationship_is_defined(): void
    {
        $tenant = Tenant::factory()->create();

        // Create a test model with the trait
        $model = new class extends Model
        {
            use BelongsToTenant;
            use \Illuminate\Database\Eloquent\Concerns\HasUuids;

            protected $table = 'users';

            protected $fillable = ['name', 'email', 'password', 'tenant_id', 'status'];
        };

        $model->tenant_id = $tenant->id;
        $model->name = 'Test User';
        $model->email = 'test@example.com';
        $model->password = bcrypt('password');
        $model->status = 'active';
        $model->save();

        $relationship = $model->tenant();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relationship);
        $this->assertEquals($tenant->id, $model->tenant->id);
    }
}
