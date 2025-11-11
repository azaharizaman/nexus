<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Core\Policies;

use Azaharizaman\Erp\Core\Models\Tenant;
use Azaharizaman\Erp\Core\Policies\TenantPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tenant Policy Test
 *
 * Unit tests for TenantPolicy authorization logic.
 */
class TenantPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected TenantPolicy $policy;

    protected User $adminUser;

    protected User $normalUser;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new TenantPolicy;
        $this->adminUser = User::factory()->create(['is_admin' => true]);
        $this->normalUser = User::factory()->create(['is_admin' => false]);
        $this->tenant = Tenant::factory()->create();
    }

    /**
     * Test that admin user can view any tenants.
     */
    public function test_admin_can_view_any_tenants(): void
    {
        $result = $this->policy->viewAny($this->adminUser);

        $this->assertTrue($result);
    }

    /**
     * Test that normal user cannot view any tenants.
     */
    public function test_normal_user_cannot_view_any_tenants(): void
    {
        $result = $this->policy->viewAny($this->normalUser);

        $this->assertFalse($result);
    }

    /**
     * Test that admin user can view a specific tenant.
     */
    public function test_admin_can_view_tenant(): void
    {
        $result = $this->policy->view($this->adminUser, $this->tenant);

        $this->assertTrue($result);
    }

    /**
     * Test that normal user cannot view a specific tenant.
     */
    public function test_normal_user_cannot_view_tenant(): void
    {
        $result = $this->policy->view($this->normalUser, $this->tenant);

        $this->assertFalse($result);
    }

    /**
     * Test that admin user can create tenants.
     */
    public function test_admin_can_create_tenant(): void
    {
        $result = $this->policy->create($this->adminUser);

        $this->assertTrue($result);
    }

    /**
     * Test that normal user cannot create tenants.
     */
    public function test_normal_user_cannot_create_tenant(): void
    {
        $result = $this->policy->create($this->normalUser);

        $this->assertFalse($result);
    }

    /**
     * Test that admin user can update tenants.
     */
    public function test_admin_can_update_tenant(): void
    {
        $result = $this->policy->update($this->adminUser, $this->tenant);

        $this->assertTrue($result);
    }

    /**
     * Test that normal user cannot update tenants.
     */
    public function test_normal_user_cannot_update_tenant(): void
    {
        $result = $this->policy->update($this->normalUser, $this->tenant);

        $this->assertFalse($result);
    }

    /**
     * Test that admin user can delete tenants.
     */
    public function test_admin_can_delete_tenant(): void
    {
        $result = $this->policy->delete($this->adminUser, $this->tenant);

        $this->assertTrue($result);
    }

    /**
     * Test that normal user cannot delete tenants.
     */
    public function test_normal_user_cannot_delete_tenant(): void
    {
        $result = $this->policy->delete($this->normalUser, $this->tenant);

        $this->assertFalse($result);
    }

    /**
     * Test that admin user can restore tenants.
     */
    public function test_admin_can_restore_tenant(): void
    {
        $result = $this->policy->restore($this->adminUser, $this->tenant);

        $this->assertTrue($result);
    }

    /**
     * Test that normal user cannot restore tenants.
     */
    public function test_normal_user_cannot_restore_tenant(): void
    {
        $result = $this->policy->restore($this->normalUser, $this->tenant);

        $this->assertFalse($result);
    }

    /**
     * Test that admin user can force delete tenants.
     */
    public function test_admin_can_force_delete_tenant(): void
    {
        $result = $this->policy->forceDelete($this->adminUser, $this->tenant);

        $this->assertTrue($result);
    }

    /**
     * Test that normal user cannot force delete tenants.
     */
    public function test_normal_user_cannot_force_delete_tenant(): void
    {
        $result = $this->policy->forceDelete($this->normalUser, $this->tenant);

        $this->assertFalse($result);
    }
}
