<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Core\Enums;

use Azaharizaman\Erp\Core\Enums\TenantStatus;
use PHPUnit\Framework\TestCase;

class TenantStatusTest extends TestCase
{
    /**
     * Test that all required enum cases exist.
     */
    public function test_has_all_required_cases(): void
    {
        $cases = TenantStatus::cases();
        $values = array_column($cases, 'value');

        $this->assertContains('active', $values);
        $this->assertContains('suspended', $values);
        $this->assertContains('archived', $values);
    }

    /**
     * Test that values method returns all enum values.
     */
    public function test_values_method_returns_all_enum_values(): void
    {
        $values = TenantStatus::values();

        $this->assertIsArray($values);
        $this->assertCount(3, $values);
        $this->assertContains('active', $values);
        $this->assertContains('suspended', $values);
        $this->assertContains('archived', $values);
    }

    /**
     * Test that label method returns correct labels.
     */
    public function test_label_method_returns_correct_labels(): void
    {
        $this->assertEquals('Active', TenantStatus::ACTIVE->label());
        $this->assertEquals('Suspended', TenantStatus::SUSPENDED->label());
        $this->assertEquals('Archived', TenantStatus::ARCHIVED->label());
    }

    /**
     * Test that enum can be instantiated from string value.
     */
    public function test_can_be_instantiated_from_string_value(): void
    {
        $this->assertEquals(TenantStatus::ACTIVE, TenantStatus::from('active'));
        $this->assertEquals(TenantStatus::SUSPENDED, TenantStatus::from('suspended'));
        $this->assertEquals(TenantStatus::ARCHIVED, TenantStatus::from('archived'));
    }
}
