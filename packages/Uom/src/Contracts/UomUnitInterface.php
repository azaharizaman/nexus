<?php

declare(strict_types=1);

namespace Nexus\Uom\Contracts;

/**
 * UOM Unit Data Structure Contract
 *
 * Defines the data structure for a unit of measurement.
 * This interface describes what a Unit IS (data structure),
 * not how it's stored or retrieved.
 */
interface UomUnitInterface
{
    /**
     * Get the unique identifier of the unit
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Get the unit code/symbol (e.g., 'kg', 'm', 'l')
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Get the human-readable name of the unit
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the description of the unit
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Get the UOM type ID this unit belongs to
     *
     * @return int
     */
    public function getUomTypeId(): int;

    /**
     * Get the conversion factor to the base unit
     *
     * @return string|float Decimal value. For high-precision calculations, the value should be returned as a string.
     * @note The string type is preferred for precision. Implementers should use high-precision decimal libraries (e.g., Brick\Math\BigDecimal) for arithmetic.
     */
    public function getConversionFactor(): string|float;

    /**
     * Get the offset for conversion (used for temperature, etc.)
     *
     * @return string|float|null Decimal value. For high-precision calculations, the value should be returned as a string.
     * @note The string type is preferred for precision. Implementers should use high-precision decimal libraries (e.g., Brick\Math\BigDecimal) for arithmetic.
     */
    public function getOffset(): string|float|null;

    /**
     * Get the precision (decimal places) for this unit
     *
     * @return int
     */
    public function getPrecision(): int;

    /**
     * Check if this is the base unit for its type
     *
     * @return bool
     */
    public function isBase(): bool;

    /**
     * Check if this unit is active
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Get the sort order
     *
     * @return int
     */
    public function getSortOrder(): int;

    /**
     * Get additional metadata
     *
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array;
}
