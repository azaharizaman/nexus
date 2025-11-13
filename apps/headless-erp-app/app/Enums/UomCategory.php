<?php

declare(strict_types=1);

namespace Nexus\Erp\Enums;

/**
 * Unit of Measure Categories
 *
 * Standard categories for organizing units of measure across the ERP system.
 * Each category has a standard base unit for conversions.
 */
enum UomCategory: string
{
    case LENGTH = 'LENGTH';
    case MASS = 'MASS';
    case VOLUME = 'VOLUME';
    case AREA = 'AREA';
    case COUNT = 'COUNT';
    case TIME = 'TIME';

    /**
     * Get human-readable label for the category
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::LENGTH => 'Length',
            self::MASS => 'Mass',
            self::VOLUME => 'Volume',
            self::AREA => 'Area',
            self::COUNT => 'Count/Quantity',
            self::TIME => 'Time',
        };
    }

    /**
     * Get the standard base unit code for this category
     *
     * This is used as the reference unit for all conversion calculations
     * within this category.
     *
     * @return string Standard base unit code (e.g., 'm' for LENGTH)
     */
    public function baseUnit(): string
    {
        return match ($this) {
            self::LENGTH => 'm',        // meter
            self::MASS => 'kg',         // kilogram
            self::VOLUME => 'L',        // liter
            self::AREA => 'm²',         // square meter
            self::COUNT => 'pc',        // piece
            self::TIME => 's',          // second
        };
    }

    /**
     * Get all available category values
     *
     * Useful for validation rules: Rule::in(UomCategory::values())
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /**
     * Get all cases as associative array for dropdown options
     *
     * Format: ['LENGTH' => 'Length', 'MASS' => 'Mass', ...]
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }
}
