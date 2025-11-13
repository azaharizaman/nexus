<?php

declare(strict_types=1);

namespace Nexus\Erp\Core\Enums;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case LOCKED = 'locked';
    case SUSPENDED = 'suspended';

    /**
     * Get all enum values as an array
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label for the status
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::LOCKED => 'Locked',
            self::SUSPENDED => 'Suspended',
        };
    }

    /**
     * Check if the status allows login
     */
    public function canLogin(): bool
    {
        return match ($this) {
            self::ACTIVE => true,
            self::INACTIVE, self::LOCKED, self::SUSPENDED => false,
        };
    }

    /**
     * Check if the status is active
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
