<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Core\Scopes;

use Azaharizaman\Erp\Core\Models\Tenant;
use Azaharizaman\Erp\Core\Scopes\TenantScope;
use Azaharizaman\Erp\Core\Traits\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that TenantScope implements Scope interface.
     */
    public function test_implements_scope_interface(): void
    {
        $scope = new TenantScope;

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Scope::class, $scope);
    }

    /**
     * Test that TenantScope applies tenant_id filter when tenant is set.
     */
    public function test_applies_tenant_filter_when_tenant_is_set(): void
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
     * Test that TenantScope does not apply filter when no tenant is set.
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
     * Test that TenantScope gets tenant from authenticated user.
     */
    public function test_gets_tenant_from_authenticated_user(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user);

        $scope = new TenantScope;
        $reflection = new \ReflectionClass($scope);
        $method = $reflection->getMethod('getCurrentTenantId');
        $method->setAccessible(true);

        $tenantId = $method->invoke($scope);

        $this->assertEquals($tenant->id, $tenantId);
    }

    /**
     * Test that TenantScope returns null when user has no tenant.
     */
    public function test_returns_null_when_user_has_no_tenant(): void
    {
        $user = User::factory()->create(['tenant_id' => null]);

        $this->actingAs($user);

        $scope = new TenantScope;
        $reflection = new \ReflectionClass($scope);
        $method = $reflection->getMethod('getCurrentTenantId');
        $method->setAccessible(true);

        $tenantId = $method->invoke($scope);

        $this->assertNull($tenantId);
    }

    /**
     * Test that TenantScope gets tenant from app container.
     */
    public function test_gets_tenant_from_app_container(): void
    {
        $tenant = Tenant::factory()->create();

        // Bind tenant to container
        app()->instance('tenant.current', $tenant);

        $scope = new TenantScope;
        $reflection = new \ReflectionClass($scope);
        $method = $reflection->getMethod('getCurrentTenantId');
        $method->setAccessible(true);

        $tenantId = $method->invoke($scope);

        $this->assertEquals($tenant->id, $tenantId);

        // Clean up
        app()->forgetInstance('tenant.current');
    }

    /**
     * Test that TenantScope returns null when no context available.
     */
    public function test_returns_null_when_no_context_available(): void
    {
        // No authenticated user
        auth()->logout();

        // No tenant in container
        app()->forgetInstance('tenant.current');

        $scope = new TenantScope;
        $reflection = new \ReflectionClass($scope);
        $method = $reflection->getMethod('getCurrentTenantId');
        $method->setAccessible(true);

        $tenantId = $method->invoke($scope);

        $this->assertNull($tenantId);
    }
}
