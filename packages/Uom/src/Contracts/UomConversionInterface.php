<?php

declare(strict_types=1);

namespace Nexus\Uom\Contracts;

/**
 * UOM Conversion Data Structure Contract
 *
 * Defines the data structure for a conversion rule between two units.
 */
interface UomConversionInterface
{
    /**
     * Get the unique identifier of the conversion
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Get the source unit ID
     *
     * @return int
     */
    public function getSourceUnitId(): int;

    /**
     * Get the target unit ID
     *
     * @return int
     */
    public function getTargetUnitId(): int;

    /**
     * Get the conversion factor
     *
     * @return string|float Decimal value
     */
    public function getFactor(): string|float;

    /**
     * Get the offset for conversion
     *
     * @return string|float|null Decimal value
     */
    public function getOffset(): string|float|null;

    /**
     * Get the formula for conversion (optional)
     *
     * @return string|null
     */
    public function getFormula(): ?string;

    /**
     * Check if this conversion is bidirectional
     *
     * @return bool
     */
    public function isBidirectional(): bool;

    /**
     * Check if this conversion is active
     *
     * @return bool
     */
    public function isActive(): bool;
}
