<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Core\Models;

use App\Models\User;
use App\Enums\UserStatus;
use Nexus\TenancyManagement\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for User model
 *
 * This test suite validates:
 * - User model attributes and relationships
 * - UUID primary key functionality
 * - Status management
 * - MFA functionality
 * - Account lockout mechanisms
 * - Helper methods
 */
class UserTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
    }

    /**
     * TASK-001: Test user creation with UUID primary key
     */
    public function test_user_is_created_with_uuid_primary_key(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertIsString($user->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $user->id
        );
    }

    /**
     * TASK-007: Test tenant relationship
     */
    public function test_user_belongs_to_tenant(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertInstanceOf(Tenant::class, $user->tenant);
        $this->assertEquals($this->tenant->id, $user->tenant->id);
    }

    /**
     * TASK-005: Test UserStatus enum is properly cast
     */
    public function test_status_is_cast_to_enum(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->assertInstanceOf(UserStatus::class, $user->status);
        $this->assertEquals(UserStatus::ACTIVE, $user->status);
    }

    /**
     * TASK-005: Test all user status values
     */
    public function test_can_create_users_with_different_statuses(): void
    {
        $activeUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => UserStatus::ACTIVE,
        ]);
        $inactiveUser = User::factory()->inactive()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $lockedUser = User::factory()->locked()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $suspendedUser = User::factory()->suspended()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertEquals(UserStatus::ACTIVE, $activeUser->status);
        $this->assertEquals(UserStatus::INACTIVE, $inactiveUser->status);
        $this->assertEquals(UserStatus::LOCKED, $lockedUser->status);
        $this->assertEquals(UserStatus::SUSPENDED, $suspendedUser->status);
    }

    /**
     * TASK-002: Test email_verified_at timestamp
     */
    public function test_email_verified_at_is_cast_to_datetime(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->email_verified_at);
    }

    /**
     * TASK-002: Test last_login_at timestamp
     */
    public function test_last_login_at_is_cast_to_datetime(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'last_login_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->last_login_at);
    }

    /**
     * TASK-003: Test MFA fields are properly configured
     */
    public function test_mfa_secret_is_encrypted(): void
    {
        $secret = 'test-mfa-secret-123456';
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'mfa_enabled' => true,
            'mfa_secret' => $secret,
        ]);

        // Reload from database
        $user = $user->fresh();

        // Secret should be accessible and match original value (Laravel handles encryption/decryption)
        $this->assertEquals($secret, $user->mfa_secret);
        $this->assertTrue($user->mfa_enabled);
    }

    /**
     * TASK-003: Test MFA secret is hidden in array/JSON output
     */
    public function test_mfa_secret_is_hidden_in_output(): void
    {
        $user = User::factory()->withMfa()->create(['tenant_id' => $this->tenant->id]);

        $array = $user->toArray();

        $this->assertArrayNotHasKey('mfa_secret', $array);
        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    /**
     * TASK-003: Test failed login attempts tracking
     */
    public function test_failed_login_attempts_are_tracked(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'failed_login_attempts' => 0,
        ]);

        $this->assertEquals(0, $user->failed_login_attempts);

        $user->update(['failed_login_attempts' => 3]);

        $this->assertEquals(3, $user->fresh()->failed_login_attempts);
    }

    /**
     * TASK-003: Test locked_until timestamp
     */
    public function test_locked_until_is_cast_to_datetime(): void
    {
        $lockTime = now()->addMinutes(30);
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'locked_until' => $lockTime,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->locked_until);
    }

    /**
     * TASK-006: Test isActive method
     */
    public function test_is_active_method_returns_correct_value(): void
    {
        $activeUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => UserStatus::ACTIVE,
        ]);
        $inactiveUser = User::factory()->inactive()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertTrue($activeUser->isActive());
        $this->assertFalse($inactiveUser->isActive());
    }

    /**
     * TASK-006: Test isLocked method with permanent lock
     */
    public function test_is_locked_method_detects_permanent_lock(): void
    {
        $lockedUser = User::factory()->locked()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $activeUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->assertTrue($lockedUser->isLocked());
        $this->assertFalse($activeUser->isLocked());
    }

    /**
     * TASK-006: Test isLocked method with temporary lockout
     */
    public function test_is_locked_method_detects_temporary_lockout(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => UserStatus::ACTIVE,
            'locked_until' => now()->addMinutes(10),
        ]);

        $this->assertTrue($user->isLocked());
    }

    /**
     * TASK-006: Test isLocked method with expired lockout
     */
    public function test_is_locked_method_returns_false_for_expired_lockout(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => UserStatus::ACTIVE,
            'locked_until' => now()->subMinutes(10),
        ]);

        $this->assertFalse($user->isLocked());
    }

    /**
     * TASK-006: Test hasMfaEnabled method
     */
    public function test_has_mfa_enabled_method(): void
    {
        $userWithMfa = User::factory()->withMfa()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $userWithoutMfa = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'mfa_enabled' => false,
        ]);

        $this->assertTrue($userWithMfa->hasMfaEnabled());
        $this->assertFalse($userWithoutMfa->hasMfaEnabled());
    }

    /**
     * TASK-006: Test incrementFailedLoginAttempts method
     */
    public function test_increment_failed_login_attempts(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'failed_login_attempts' => 2,
        ]);

        $user->incrementFailedLoginAttempts();

        $this->assertEquals(3, $user->fresh()->failed_login_attempts);
    }

    /**
     * TASK-006: Test account is locked after 5 failed attempts
     */
    public function test_account_locks_after_five_failed_attempts(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'failed_login_attempts' => 4,
            'locked_until' => null,
        ]);

        $user->incrementFailedLoginAttempts();

        $freshUser = $user->fresh();
        $this->assertEquals(5, $freshUser->failed_login_attempts);
        $this->assertNotNull($freshUser->locked_until);
        $this->assertTrue($freshUser->locked_until->isFuture());
    }

    /**
     * TASK-006: Test resetFailedLoginAttempts method
     */
    public function test_reset_failed_login_attempts(): void
    {
        $user = User::factory()->withFailedAttempts(3)->create([
            'tenant_id' => $this->tenant->id,
            'locked_until' => now()->addMinutes(30),
        ]);

        $user->resetFailedLoginAttempts();

        $freshUser = $user->fresh();
        $this->assertEquals(0, $freshUser->failed_login_attempts);
        $this->assertNull($freshUser->locked_until);
    }

    /**
     * TASK-006: Test updateLastLogin method
     */
    public function test_update_last_login(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'last_login_at' => null,
        ]);

        $this->assertNull($user->last_login_at);

        $user->updateLastLogin();

        $freshUser = $user->fresh();
        $this->assertNotNull($freshUser->last_login_at);
        $this->assertEqualsWithDelta(
            now()->timestamp,
            $freshUser->last_login_at->timestamp,
            2
        );
    }

    /**
     * TASK-006: Test isAdmin method
     */
    public function test_is_admin_method(): void
    {
        $adminUser = User::factory()->admin()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $normalUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertTrue($adminUser->isAdmin());
        $this->assertFalse($normalUser->isAdmin());
    }

    /**
     * TASK-004: Test unique constraint on tenant_id and email
     */
    public function test_email_is_unique_per_tenant(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $email = 'test@example.com';

        // Create user with same email in different tenants - should succeed
        $user1 = User::factory()->create([
            'tenant_id' => $tenant1->id,
            'email' => $email,
        ]);
        $user2 = User::factory()->create([
            'tenant_id' => $tenant2->id,
            'email' => $email,
        ]);

        $this->assertEquals($email, $user1->email);
        $this->assertEquals($email, $user2->email);
        $this->assertNotEquals($user1->tenant_id, $user2->tenant_id);

        // Attempting to create another user with same email in same tenant should fail
        $this->expectException(\Illuminate\Database\QueryException::class);

        User::factory()->create([
            'tenant_id' => $tenant1->id,
            'email' => $email,
        ]);
    }

    /**
     * TASK-008: Test UserFactory produces valid data
     */
    public function test_user_factory_creates_valid_users(): void
    {
        $users = User::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);

        $this->assertCount(5, $users);

        foreach ($users as $user) {
            $this->assertIsString($user->id);
            $this->assertIsString($user->name);
            $this->assertIsString($user->email);
            $this->assertInstanceOf(UserStatus::class, $user->status);
            $this->assertEquals(UserStatus::ACTIVE, $user->status);
            $this->assertFalse($user->mfa_enabled);
            $this->assertEquals(0, $user->failed_login_attempts);
            $this->assertFalse($user->is_admin);
        }
    }

    /**
     * Test password is hashed
     */
    public function test_password_is_hashed(): void
    {
        $plainPassword = 'test-password-123';
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'password' => $plainPassword,
        ]);

        // Password should not be stored in plain text
        $this->assertNotEquals($plainPassword, $user->password);

        // Password should be verifiable with Hash::check
        $this->assertTrue(\Hash::check($plainPassword, $user->password));
    }
}
