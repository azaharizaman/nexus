<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Core\Middleware;

use Azaharizaman\Erp\Core\Contracts\TenantManagerContract;
use Azaharizaman\Erp\Core\Middleware\IdentifyTenant;
use Azaharizaman\Erp\Core\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class IdentifyTenantTest extends TestCase
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
     * Test middleware resolves tenant from authenticated user
     */
    public function test_middleware_resolves_tenant_from_authenticated_user(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Test Tenant']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user);

        $request = Request::create('/api/test', 'GET');
        $middleware = new IdentifyTenant($this->tenantManager);

        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($this->tenantManager->current());
        $this->assertEquals($tenant->id, $this->tenantManager->current()->id);
    }

    /**
     * Test middleware returns 401 when user is not authenticated
     */
    public function test_middleware_returns_401_when_not_authenticated(): void
    {
        $request = Request::create('/api/test', 'GET');
        $middleware = new IdentifyTenant($this->tenantManager);

        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Unauthenticated.', $data['message']);
    }

    /**
     * Test middleware returns 403 when user has no tenant_id
     */
    public function test_middleware_returns_403_when_user_has_no_tenant(): void
    {
        $user = User::factory()->create(['tenant_id' => null]);

        $this->actingAs($user);

        $request = Request::create('/api/test', 'GET');
        $middleware = new IdentifyTenant($this->tenantManager);

        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('User does not belong to any tenant.', $data['message']);
    }

    /**
     * Test middleware returns 404 when tenant not found
     */
    public function test_middleware_returns_404_when_tenant_not_found(): void
    {
        // Create user with a tenant, then delete the tenant to simulate non-existent tenant
        $tenant = Tenant::factory()->create(['name' => 'Test Tenant']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        // Delete the tenant to make it non-existent
        $tenant->delete();

        $this->actingAs($user);

        $request = Request::create('/api/test', 'GET');
        $middleware = new IdentifyTenant($this->tenantManager);

        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(404, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Tenant not found.', $data['message']);
    }

    /**
     * Test tenant context persists through request lifecycle
     */
    public function test_tenant_context_persists_through_request_lifecycle(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Persistent Tenant']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user);

        $request = Request::create('/api/test', 'GET');
        $middleware = new IdentifyTenant($this->tenantManager);

        $capturedTenantId = null;

        $response = $middleware->handle($request, function ($req) use (&$capturedTenantId) {
            // Simulate controller action checking tenant
            $capturedTenantId = $this->tenantManager->current()?->id;

            return response()->json(['tenant_id' => $capturedTenantId]);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($tenant->id, $capturedTenantId);
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($tenant->id, $data['tenant_id']);
    }

    /**
     * Test middleware sets different tenants for different users
     */
    public function test_middleware_sets_different_tenants_for_different_users(): void
    {
        $tenant1 = Tenant::factory()->create(['name' => 'Tenant 1']);
        $tenant2 = Tenant::factory()->create(['name' => 'Tenant 2']);

        $user1 = User::factory()->create(['tenant_id' => $tenant1->id]);
        $user2 = User::factory()->create(['tenant_id' => $tenant2->id]);

        // Request from user 1
        $this->actingAs($user1);
        $request1 = Request::create('/api/test', 'GET');
        $middleware = new IdentifyTenant($this->tenantManager);

        $middleware->handle($request1, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals($tenant1->id, $this->tenantManager->current()->id);

        // Request from user 2
        $this->actingAs($user2);
        $request2 = Request::create('/api/test', 'GET');

        $middleware->handle($request2, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals($tenant2->id, $this->tenantManager->current()->id);
    }

    /**
     * Test middleware integration with API routes
     */
    public function test_middleware_integration_with_api_routes(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'API Test Tenant']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        // Create a test route that uses the middleware
        $this->app['router']->middleware(['api', IdentifyTenant::class])
            ->get('/api/tenant-test', function () {
                return response()->json([
                    'tenant_id' => tenant()?->id,
                    'tenant_name' => tenant()?->name,
                ]);
            });

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/tenant-test');

        $response->assertOk();
        $response->assertJson([
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
        ]);
    }
}
