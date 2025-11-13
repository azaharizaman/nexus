<?php

declare(strict_types=1);

namespace Nexus\Erp\Services\UnitOfMeasure;

use Nexus\Erp\Contracts\UomRepositoryContract;
use Nexus\Erp\Exceptions\UnitOfMeasure\IncompatibleUomException;
use Nexus\Erp\Exceptions\UnitOfMeasure\UomNotFoundException;
use Nexus\Erp\Models\Uom;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * UOM Conversion Service
 *
 * Provides precision-safe quantity conversion between compatible units of measure
 * using brick/math BigDecimal for accurate decimal arithmetic.
 *
 * All conversions are performed via the category's base unit in two steps:
 * 1. Convert from source unit to base unit (multiply by conversion factor)
 * 2. Convert from base unit to target unit (divide by conversion factor)
 *
 * This ensures consistent accuracy and avoids accumulating rounding errors.
 */
class UomConversionService
{
    /**
     * Create a new UOM conversion service
     *
     * @param  UomRepositoryContract  $repository  UOM repository for database access
     */
    public function __construct(
        protected readonly UomRepositoryContract $repository
    ) {}

    /**
     * Convert quantity from one unit to another
     *
     * Performs precision-safe conversion using BigDecimal arithmetic.
     * Only compatible units (same category) can be converted.
     *
     * @param  string|BigDecimal  $quantity  Quantity to convert (as string or BigDecimal to avoid float precision loss)
     * @param  Uom|string  $fromUom  Source unit (Uom model or code string)
     * @param  Uom|string  $toUom  Target unit (Uom model or code string)
     * @param  int|null  $precision  Decimal precision (null = use target UOM's precision)
     * @param  RoundingMode  $roundingMode  Rounding mode (default: HALF_UP)
     * @return string String representation of converted quantity (preserves precision)
     *
     * @throws UomNotFoundException If either UOM code is not found
     * @throws IncompatibleUomException If UOMs are from different categories
     */
    public function convert(
        string|BigDecimal $quantity,
        Uom|string $fromUom,
        Uom|string $toUom,
        ?int $precision = null,
        RoundingMode $roundingMode = RoundingMode::HALF_UP
    ): string {
        // Resolve UOM models from codes if strings provided
        $from = $this->resolveUom($fromUom);
        $to = $this->resolveUom($toUom);

        // Validate category compatibility
        if ($from->category !== $to->category) {
            throw new IncompatibleUomException(
                $from->category->value,
                $to->category->value
            );
        }

        // Handle direct conversion (same unit)
        if ($from->code === $to->code) {
            $quantityBd = $quantity instanceof BigDecimal ? $quantity : BigDecimal::of($quantity);

            return $quantityBd->toScale($precision ?? 10, $roundingMode)->__toString();
        }

        // Two-step conversion via base unit
        // Step 1: Convert from source to base unit
        $baseQuantity = $this->convertToBaseUnit($quantity, $from, $roundingMode);

        // Step 2: Convert from base to target unit
        return $this->convertFromBaseUnit($baseQuantity, $to, $precision, $roundingMode);
    }

    /**
     * Convert quantity to category's base unit
     *
     * Multiplies quantity by the source UOM's conversion factor.
     *
     * @param  string|BigDecimal  $quantity  Quantity to convert
     * @param  Uom|string  $uom  Source UOM
     * @param  RoundingMode  $roundingMode  Rounding mode (default: HALF_UP)
     * @return string String representation of base unit quantity
     *
     * @throws UomNotFoundException If UOM code is not found
     */
    public function convertToBaseUnit(
        string|BigDecimal $quantity,
        Uom|string $uom,
        RoundingMode $roundingMode = RoundingMode::HALF_UP
    ): string {
        $resolvedUom = $this->resolveUom($uom);

        $quantityBd = $quantity instanceof BigDecimal ? $quantity : BigDecimal::of($quantity);
        $conversionFactor = BigDecimal::of($resolvedUom->conversion_factor);

        // Multiply by conversion factor to get base unit
        $result = $quantityBd->multipliedBy($conversionFactor);

        // Use high precision for intermediate calculation (20 decimals)
        return $result->toScale(20, $roundingMode)->__toString();
    }

    /**
     * Convert quantity from base unit to target unit
     *
     * Divides base unit quantity by the target UOM's conversion factor.
     *
     * @param  string|BigDecimal  $baseQuantity  Quantity in base unit
     * @param  Uom|string  $targetUom  Target UOM
     * @param  int|null  $precision  Decimal precision (null = use UOM's precision, default: 10)
     * @param  RoundingMode  $roundingMode  Rounding mode (default: HALF_UP)
     * @return string String representation of converted quantity
     *
     * @throws UomNotFoundException If UOM code is not found
     */
    public function convertFromBaseUnit(
        string|BigDecimal $baseQuantity,
        Uom|string $targetUom,
        ?int $precision = null,
        RoundingMode $roundingMode = RoundingMode::HALF_UP
    ): string {
        $resolvedUom = $this->resolveUom($targetUom);

        $baseBd = $baseQuantity instanceof BigDecimal ? $baseQuantity : BigDecimal::of($baseQuantity);
        $conversionFactor = BigDecimal::of($resolvedUom->conversion_factor);

        // Divide by conversion factor to get target unit
        $scale = $precision ?? 10; // Default to 10 decimals if not specified
        $result = $baseBd->dividedBy($conversionFactor, $scale, $roundingMode);

        return $result->__toString();
    }

    /**
     * Resolve UOM from model or code string
     *
     * @param  Uom|string  $uom  UOM model or code string
     * @return Uom Resolved UOM model
     *
     * @throws UomNotFoundException If UOM code is not found
     */
    protected function resolveUom(Uom|string $uom): Uom
    {
        if ($uom instanceof Uom) {
            return $uom;
        }

        $resolved = $this->repository->findByCode($uom);

        if ($resolved === null) {
            throw new UomNotFoundException($uom);
        }

        return $resolved;
    }
}
