<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands\Tenant;

use Nexus\TenancyManagement\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTenantCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test can create tenant with all required options
     */
    public function test_can_create_tenant_with_required_options(): void
    {
        $this->artisan('erp:tenant:create', [
            '--name' => 'Test Company',
            '--domain' => 'test.example.com',
            '--email' => 'contact@test.com',
        ])
            ->expectsOutput('Creating a new tenant...')
            ->expectsOutput('✓ Tenant created successfully!')
            ->assertExitCode(0);

        // Verify tenant was created in database
        $this->assertDatabaseHas('tenants', [
            'name' => 'Test Company',
            'domain' => 'test.example.com',
            'contact_email' => 'contact@test.com',
        ]);
    }

    /**
     * Test can create tenant with all options
     */
    public function test_can_create_tenant_with_all_options(): void
    {
        $this->artisan('erp:tenant:create', [
            '--name' => 'Full Test Company',
            '--domain' => 'fulltest.example.com',
            '--email' => 'contact@fulltest.com',
            '--contact-name' => 'John Doe',
            '--contact-phone' => '+1234567890',
            '--billing-email' => 'billing@fulltest.com',
            '--subscription-plan' => 'premium',
        ])
            ->expectsOutput('Creating a new tenant...')
            ->expectsOutput('✓ Tenant created successfully!')
            ->assertExitCode(0);

        // Verify tenant was created with all fields
        $this->assertDatabaseHas('tenants', [
            'name' => 'Full Test Company',
            'domain' => 'fulltest.example.com',
            'contact_email' => 'contact@fulltest.com',
            'contact_name' => 'John Doe',
            'contact_phone' => '+1234567890',
            'billing_email' => 'billing@fulltest.com',
            'subscription_plan' => 'premium',
        ]);
    }

    /**
     * Test creates tenant with active status by default
     */
    public function test_creates_tenant_with_active_status_by_default(): void
    {
        $this->artisan('erp:tenant:create', [
            '--name' => 'Status Test',
            '--domain' => 'status.example.com',
            '--email' => 'contact@status.com',
        ])
            ->assertExitCode(0);

        $tenant = Tenant::where('domain', 'status.example.com')->first();
        $this->assertNotNull($tenant);
        $this->assertTrue($tenant->isActive());
    }

    /**
     * Test validation fails with duplicate domain
     */
    public function test_validation_fails_with_duplicate_domain(): void
    {
        // Create first tenant
        Tenant::factory()->create([
            'domain' => 'duplicate.example.com',
        ]);

        // Try to create tenant with same domain
        $this->artisan('erp:tenant:create', [
            '--name' => 'Duplicate Test',
            '--domain' => 'duplicate.example.com',
            '--email' => 'contact@duplicate.com',
        ])
            ->expectsOutput('✗ Validation failed:')
            ->assertExitCode(1);

        // Verify only one tenant with that domain exists
        $this->assertEquals(1, Tenant::where('domain', 'duplicate.example.com')->count());
    }

    /**
     * Test validation fails with invalid email
     */
    public function test_validation_fails_with_invalid_email(): void
    {
        $this->artisan('erp:tenant:create', [
            '--name' => 'Invalid Email Test',
            '--domain' => 'invalidemail.example.com',
            '--email' => 'not-an-email',
        ])
            ->expectsOutput('✗ Validation failed:')
            ->assertExitCode(1);

        // Verify tenant was not created
        $this->assertDatabaseMissing('tenants', [
            'domain' => 'invalidemail.example.com',
        ]);
    }

    /**
     * Test displays tenant details after creation
     */
    public function test_displays_tenant_details_after_creation(): void
    {
        $this->artisan('erp:tenant:create', [
            '--name' => 'Display Test',
            '--domain' => 'display.example.com',
            '--email' => 'contact@display.com',
        ])
            ->expectsOutputToContain('Display Test')
            ->expectsOutputToContain('display.example.com')
            ->expectsOutputToContain('Active')
            ->expectsOutputToContain('contact@display.com')
            ->assertExitCode(0);
    }

    /**
     * Test prompts for required fields when not provided
     */
    public function test_prompts_for_required_fields_when_not_provided(): void
    {
        $this->artisan('erp:tenant:create')
            ->expectsQuestion('Tenant name', 'Interactive Test')
            ->expectsQuestion('Tenant domain', 'interactive.example.com')
            ->expectsQuestion('Contact email', 'contact@interactive.com')
            ->expectsOutput('✓ Tenant created successfully!')
            ->assertExitCode(0);

        // Verify tenant was created
        $this->assertDatabaseHas('tenants', [
            'name' => 'Interactive Test',
            'domain' => 'interactive.example.com',
            'contact_email' => 'contact@interactive.com',
        ]);
    }

    /**
     * Test handles missing name gracefully
     */
    public function test_handles_missing_required_field_gracefully(): void
    {
        $this->artisan('erp:tenant:create', [
            '--domain' => 'missingname.example.com',
            '--email' => 'contact@missingname.com',
        ])
            ->expectsQuestion('Tenant name', '') // Provide empty name
            ->expectsOutput('✗ Validation failed:')
            ->assertExitCode(1);
    }
}
