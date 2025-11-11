<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Core\Enums;

use Azaharizaman\Erp\Core\Enums\UserStatus;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UserStatus enum
 *
 * This test suite validates:
 * - Enum values and cases
 * - Helper methods
 * - Status logic
 */
class UserStatusTest extends TestCase
{
    /**
     * TASK-005: Test all enum cases exist
     */
    public function test_has_all_required_cases(): void
    {
        $cases = UserStatus::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(UserStatus::ACTIVE, $cases);
        $this->assertContains(UserStatus::INACTIVE, $cases);
        $this->assertContains(UserStatus::LOCKED, $cases);
        $this->assertContains(UserStatus::SUSPENDED, $cases);
    }

    /**
     * TASK-005: Test enum values are correct
     */
    public function test_enum_values_are_correct(): void
    {
        $this->assertEquals('active', UserStatus::ACTIVE->value);
        $this->assertEquals('inactive', UserStatus::INACTIVE->value);
        $this->assertEquals('locked', UserStatus::LOCKED->value);
        $this->assertEquals('suspended', UserStatus::SUSPENDED->value);
    }

    /**
     * TASK-005: Test values() method returns all values
     */
    public function test_values_method_returns_all_values(): void
    {
        $values = UserStatus::values();

        $this->assertIsArray($values);
        $this->assertCount(4, $values);
        $this->assertContains('active', $values);
        $this->assertContains('inactive', $values);
        $this->assertContains('locked', $values);
        $this->assertContains('suspended', $values);
    }

    /**
     * TASK-005: Test label() method returns human-readable labels
     */
    public function test_label_method_returns_correct_labels(): void
    {
        $this->assertEquals('Active', UserStatus::ACTIVE->label());
        $this->assertEquals('Inactive', UserStatus::INACTIVE->label());
        $this->assertEquals('Locked', UserStatus::LOCKED->label());
        $this->assertEquals('Suspended', UserStatus::SUSPENDED->label());
    }

    /**
     * TASK-005: Test canLogin() method
     */
    public function test_can_login_method(): void
    {
        $this->assertTrue(UserStatus::ACTIVE->canLogin());
        $this->assertFalse(UserStatus::INACTIVE->canLogin());
        $this->assertFalse(UserStatus::LOCKED->canLogin());
        $this->assertFalse(UserStatus::SUSPENDED->canLogin());
    }

    /**
     * TASK-005: Test isActive() method
     */
    public function test_is_active_method(): void
    {
        $this->assertTrue(UserStatus::ACTIVE->isActive());
        $this->assertFalse(UserStatus::INACTIVE->isActive());
        $this->assertFalse(UserStatus::LOCKED->isActive());
        $this->assertFalse(UserStatus::SUSPENDED->isActive());
    }

    /**
     * Test enum can be created from string value
     */
    public function test_can_create_from_string_value(): void
    {
        $status = UserStatus::from('active');

        $this->assertEquals(UserStatus::ACTIVE, $status);
    }

    /**
     * Test tryFrom returns null for invalid value
     */
    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $status = UserStatus::tryFrom('invalid');

        $this->assertNull($status);
    }
}
