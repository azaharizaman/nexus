<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SerialNumbering\Enums;

/**
 * Reset Period Enum
 *
 * Defines when a sequence counter should reset to zero.
 */
enum ResetPeriod: string
{
    case NEVER = 'never';
    case DAILY = 'daily';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';

    /**
     * Get all enum values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::NEVER => 'Never',
            self::DAILY => 'Daily',
            self::MONTHLY => 'Monthly',
            self::YEARLY => 'Yearly',
        };
    }
}
