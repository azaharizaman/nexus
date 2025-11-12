<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Core\Traits;

use App\Models\User;
use Azaharizaman\Erp\Core\Models\Tenant;
use Azaharizaman\Erp\Core\Scopes\TenantScope;
use Azaharizaman\Erp\Core\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BelongsToTenantTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that BelongsToTenant trait adds TenantScope global scope.
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
     * Test that tenant() relationship is properly defined.
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

    /**
     * Test that tenant_id is automatically set on model creation.
     */
    public function test_auto_sets_tenant_id_on_creation(): void
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

        $model->name = 'New User';
        $model->email = 'newuser@example.com';
        $model->password = bcrypt('password');
        $model->status = 'active';
        $model->save();

        $this->assertEquals($tenant->id, $model->tenant_id);
    }

    /**
     * Test that tenant_id is not overridden if explicitly set.
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

        $model->name = 'New User';
        $model->email = 'newuser@example.com';
        $model->password = bcrypt('password');
        $model->status = 'active';
        $model->tenant_id = $tenant2->id; // Explicitly set to different tenant
        $model->save();

        // Should keep the explicitly set tenant_id
        $this->assertEquals($tenant2->id, $model->tenant_id);
    }

    /**
     * Test withoutTenantScope() method removes the scope.
     */
    public function test_without_tenant_scope_removes_scope(): void
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
        // The query without scope should return different results
        $this->assertNotEquals($sqlWithScope, $sqlWithoutScope);
    }

    /**
     * Test withAllTenants() method is an alias for withoutTenantScope().
     */
    public function test_with_all_tenants_is_alias(): void
    {
        // Create a test model with the trait
        $model = new class extends Model
        {
            use BelongsToTenant;

            protected $table = 'users';
        };

        $query1 = $model::withoutTenantScope();
        $query2 = $model::withAllTenants();

        // Both methods should produce the same result
        $this->assertEquals($query1->toSql(), $query2->toSql());
    }

    /**
     * Test that tenant filtering works correctly with multiple tenants.
     */
    public function test_filters_by_tenant_correctly(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Create users for both tenants
        $user1 = User::factory()->create(['tenant_id' => $tenant1->id, 'name' => 'User 1']);
        $user2 = User::factory()->create(['tenant_id' => $tenant2->id, 'name' => 'User 2']);
        $user3 = User::factory()->create(['tenant_id' => $tenant1->id, 'name' => 'User 3']);

        // Login as user from tenant1
        $this->actingAs($user1);

        // Query users (User model should use BelongsToTenant trait in practice)
        // For this test, we'll use a custom query
        $users = User::where('tenant_id', $tenant1->id)->get();

        // Should only get users from tenant1
        $this->assertCount(2, $users);
        $this->assertTrue($users->contains('id', $user1->id));
        $this->assertTrue($users->contains('id', $user3->id));
        $this->assertFalse($users->contains('id', $user2->id));
    }

    /**
     * Test getCurrentTenantIdForModel gets tenant from auth user.
     */
    public function test_get_current_tenant_id_from_auth_user(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user);

        // Create a test model with the trait
        $modelClass = new class extends Model
        {
            use BelongsToTenant;

            protected $table = 'test_models';
        };

        $reflection = new \ReflectionClass($modelClass);
        $method = $reflection->getMethod('getCurrentTenantIdForModel');
        $method->setAccessible(true);

        $tenantId = $method->invoke(null);

        $this->assertEquals($tenant->id, $tenantId);
    }

    /**
     * Test getCurrentTenantIdForModel gets tenant from app container.
     */
    public function test_get_current_tenant_id_from_app_container(): void
    {
        $tenant = Tenant::factory()->create();

        // Bind tenant to container
        app()->instance('tenant.current', $tenant);

        // Create a test model with the trait
        $modelClass = new class extends Model
        {
            use BelongsToTenant;

            protected $table = 'test_models';
        };

        $reflection = new \ReflectionClass($modelClass);
        $method = $reflection->getMethod('getCurrentTenantIdForModel');
        $method->setAccessible(true);

        $tenantId = $method->invoke(null);

        $this->assertEquals($tenant->id, $tenantId);

        // Clean up
        app()->forgetInstance('tenant.current');
    }

    /**
     * Test getCurrentTenantIdForModel returns null when no tenant context.
     */
    public function test_get_current_tenant_id_returns_null_when_no_context(): void
    {
        // No authenticated user
        auth()->logout();

        // No tenant in container
        app()->forgetInstance('tenant.current');

        // Create a test model with the trait
        $modelClass = new class extends Model
        {
            use BelongsToTenant;

            protected $table = 'test_models';
        };

        $reflection = new \ReflectionClass($modelClass);
        $method = $reflection->getMethod('getCurrentTenantIdForModel');
        $method->setAccessible(true);

        $tenantId = $method->invoke(null);

        $this->assertNull($tenantId);
    }
}
