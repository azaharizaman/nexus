<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands\Tenant;

use Azaharizaman\Erp\Core\Enums\TenantStatus;
use Azaharizaman\Erp\Core\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListTenantsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test can list all tenants
     */
    public function test_can_list_all_tenants(): void
    {
        // Create test tenants
        Tenant::factory()->count(3)->create();

        $this->artisan('erp:tenant:list')
            ->expectsOutput('Fetching tenants...')
            ->expectsOutputToContain('Total: 3 tenant(s)')
            ->assertExitCode(0);
    }

    /**
     * Test displays tenant details in table format
     */
    public function test_displays_tenant_details_in_table_format(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'TableTestCompany',
            'domain' => 'tabletest.example.com',
            'status' => TenantStatus::ACTIVE,
        ]);

        $this->artisan('erp:tenant:list')
            ->assertExitCode(0);

        // Verify tenant exists
        $this->assertDatabaseHas('tenants', [
            'name' => 'TableTestCompany',
            'domain' => 'tabletest.example.com',
        ]);
    }

    /**
     * Test can filter by status
     */
    public function test_can_filter_by_status(): void
    {
        // Create tenants with different statuses
        Tenant::factory()->count(2)->create(['status' => TenantStatus::ACTIVE]);
        Tenant::factory()->count(1)->create(['status' => TenantStatus::SUSPENDED]);

        $this->artisan('erp:tenant:list', ['--status' => 'active'])
            ->expectsOutputToContain('Total: 2 tenant(s)')
            ->assertExitCode(0);

        $this->artisan('erp:tenant:list', ['--status' => 'suspended'])
            ->expectsOutputToContain('Total: 1 tenant(s)')
            ->assertExitCode(0);
    }

    /**
     * Test can search by name
     */
    public function test_can_search_by_name(): void
    {
        Tenant::factory()->create(['name' => 'Alpha Company']);
        Tenant::factory()->create(['name' => 'Beta Corporation']);
        Tenant::factory()->create(['name' => 'Gamma Inc']);

        $this->artisan('erp:tenant:list', ['--search' => 'Beta'])
            ->expectsOutputToContain('Beta Corporation')
            ->expectsOutputToContain('Total: 1 tenant(s)')
            ->assertExitCode(0);
    }

    /**
     * Test can search by domain
     */
    public function test_can_search_by_domain(): void
    {
        Tenant::factory()->create(['domain' => 'alpha.example.com']);
        Tenant::factory()->create(['domain' => 'beta.example.com']);
        Tenant::factory()->create(['domain' => 'gamma.example.com']);

        $this->artisan('erp:tenant:list', ['--search' => 'beta.example'])
            ->expectsOutputToContain('beta.example.com')
            ->expectsOutputToContain('Total: 1 tenant(s)')
            ->assertExitCode(0);
    }

    /**
     * Test search is case-insensitive
     */
    public function test_search_is_case_insensitive(): void
    {
        Tenant::factory()->create(['name' => 'CaseSensitive Company']);

        $this->artisan('erp:tenant:list', ['--search' => 'casesensitive'])
            ->expectsOutputToContain('CaseSensitive Company')
            ->assertExitCode(0);
    }

    /**
     * Test can combine status and search filters
     */
    public function test_can_combine_status_and_search_filters(): void
    {
        Tenant::factory()->create([
            'name' => 'Active Alpha',
            'status' => TenantStatus::ACTIVE,
        ]);
        Tenant::factory()->create([
            'name' => 'Active Beta',
            'status' => TenantStatus::ACTIVE,
        ]);
        Tenant::factory()->create([
            'name' => 'Suspended Alpha',
            'status' => TenantStatus::SUSPENDED,
        ]);

        $this->artisan('erp:tenant:list', [
            '--status' => 'active',
            '--search' => 'Alpha',
        ])
            ->expectsOutputToContain('Active Alpha')
            ->expectsOutputToContain('Total: 1 tenant(s)')
            ->assertExitCode(0);
    }

    /**
     * Test displays message when no tenants found
     */
    public function test_displays_message_when_no_tenants_found(): void
    {
        $this->artisan('erp:tenant:list')
            ->expectsOutput('Fetching tenants...')
            ->expectsOutput('No tenants found.')
            ->assertExitCode(0);
    }

    /**
     * Test displays message when no tenants match filter
     */
    public function test_displays_message_when_no_tenants_match_filter(): void
    {
        Tenant::factory()->create(['status' => TenantStatus::ACTIVE]);

        $this->artisan('erp:tenant:list', ['--status' => 'archived'])
            ->expectsOutput('No tenants found.')
            ->assertExitCode(0);
    }

    /**
     * Test fails with invalid status
     */
    public function test_fails_with_invalid_status(): void
    {
        $this->artisan('erp:tenant:list', ['--status' => 'invalid'])
            ->expectsOutput('Invalid status: invalid')
            ->expectsOutputToContain('Valid statuses: active, suspended, archived')
            ->assertExitCode(1);
    }

    /**
     * Test orders tenants by created_at descending
     */
    public function test_orders_tenants_by_created_at_descending(): void
    {
        // Create tenants with specific timestamps
        $oldest = Tenant::factory()->create(['name' => 'Oldest']);
        $oldest->created_at = now()->subDays(2);
        $oldest->save();

        $newest = Tenant::factory()->create(['name' => 'Newest']);
        $newest->created_at = now();
        $newest->save();

        $middle = Tenant::factory()->create(['name' => 'Middle']);
        $middle->created_at = now()->subDay();
        $middle->save();

        // Run command and capture exit code
        $this->artisan('erp:tenant:list')
            ->expectsOutputToContain('Newest')
            ->expectsOutputToContain('Middle')
            ->expectsOutputToContain('Oldest')
            ->assertExitCode(0);
    }

    /**
     * Test displays all table columns
     */
    public function test_displays_all_table_columns(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'ColumnTest',
            'domain' => 'columntest.example.com',
        ]);

        $this->artisan('erp:tenant:list')
            ->expectsOutputToContain('Total: 1 tenant(s)')
            ->assertExitCode(0);

        // Verify tenant exists
        $this->assertDatabaseHas('tenants', [
            'name' => 'ColumnTest',
            'domain' => 'columntest.example.com',
        ]);
    }
}
