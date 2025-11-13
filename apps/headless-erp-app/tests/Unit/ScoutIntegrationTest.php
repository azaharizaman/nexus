<?php

namespace Tests\Unit;

use App\Models\User;
use Nexus\TenancyManagement\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoutIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_model_uses_searchable_trait(): void
    {
        $user = new User();
        // Check for our wrapper trait instead of direct Scout trait
        $this->assertContains('App\Support\Traits\IsSearchable', class_uses($user));
    }

    public function test_user_model_has_searchable_as_method(): void
    {
        $user = new User();
        $this->assertEquals('users', $user->searchableAs());
    }

    public function test_user_model_has_to_searchable_array_method(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'tenant_id' => $tenant->id,
        ]);

        $searchableArray = $user->toSearchableArray();

        $this->assertArrayHasKey('id', $searchableArray);
        $this->assertArrayHasKey('name', $searchableArray);
        $this->assertArrayHasKey('email', $searchableArray);
        $this->assertArrayHasKey('tenant_id', $searchableArray);
        $this->assertEquals('John Doe', $searchableArray['name']);
        $this->assertEquals('john@example.com', $searchableArray['email']);
        $this->assertEquals($tenant->id, $searchableArray['tenant_id']);
    }

    public function test_tenant_model_uses_searchable_trait(): void
    {
        $tenant = new Tenant();
        // Check for our wrapper trait instead of direct Scout trait
        $this->assertContains('App\Support\Traits\IsSearchable', class_uses($tenant));
    }

    public function test_tenant_model_has_searchable_as_method(): void
    {
        $tenant = new Tenant();
        $this->assertEquals('tenants', $tenant->searchableAs());
    }

    public function test_tenant_model_has_to_searchable_array_method(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Test Company',
            'domain' => 'testcompany.com',
        ]);

        $searchableArray = $tenant->toSearchableArray();

        $this->assertArrayHasKey('id', $searchableArray);
        $this->assertArrayHasKey('name', $searchableArray);
        $this->assertArrayHasKey('domain', $searchableArray);
        $this->assertEquals('Test Company', $searchableArray['name']);
        $this->assertEquals('testcompany.com', $searchableArray['domain']);
    }
}
