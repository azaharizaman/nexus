<?php

declare(strict_types=1);

namespace Nexus\Erp\Actions\UnitOfMeasure;

use Nexus\Erp\Exceptions\UnitOfMeasure\InvalidQuantityException;
use Nexus\Erp\Services\UnitOfMeasure\UomConversionService;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Convert Quantity Action
 *
 * Primary entry point for quantity conversion across all modules.
 * Provides caching, performance monitoring, and multiple invocation methods
 * (direct call, Artisan command, queue job).
 *
 * Example usage:
 * - Direct: ConvertQuantityAction::run('100', 'kg', 'lb')
 * - Command: php artisan uom:convert 100 kg lb
 * - Job: ConvertQuantityAction::dispatch('100', 'kg', 'lb')
 */
class ConvertQuantityAction
{
    use \Lorisleiva\Actions\Concerns\AsAction;

    /**
     * Cache TTL in seconds (24 hours)
     */
    protected const CACHE_TTL = 86400;

    /**
     * Performance threshold in milliseconds
     */
    protected const PERFORMANCE_THRESHOLD_MS = 5.0;

    /**
     * Create a new action instance
     *
     * @param  UomConversionService  $conversionService  Conversion service
     */
    public function __construct(
        protected readonly UomConversionService $conversionService
    ) {}

    /**
     * Convert quantity between units
     *
     * @param  string  $quantity  Quantity to convert (must be numeric and > 0)
     * @param  string  $fromUomCode  Source UOM code
     * @param  string  $toUomCode  Target UOM code
     * @param  string|null  $tenantId  Tenant ID (optional)
     * @return array<string, string> Conversion result with keys: quantity, from_uom, to_uom, conversion_factor
     *
     * @throws InvalidQuantityException If quantity is invalid
     */
    public function handle(
        string $quantity,
        string $fromUomCode,
        string $toUomCode,
        ?string $tenantId = null
    ): array {
        // Validate quantity
        $this->validateQuantity($quantity);

        // Start performance monitoring
        $startTime = microtime(true);

        // Check cache for conversion factor
        $cacheKey = $this->getCacheKey($fromUomCode, $toUomCode);
        $conversionFactor = Cache::get($cacheKey);

        // Perform conversion
        $convertedQuantity = $this->conversionService->convert(
            $quantity,
            $fromUomCode,
            $toUomCode
        );

        // Cache conversion factor for future use
        if ($conversionFactor === null) {
            try {
                // Calculate and cache the conversion factor
                $factor = $this->calculateConversionFactor($fromUomCode, $toUomCode);
                Cache::put($cacheKey, $factor, self::CACHE_TTL);
                $conversionFactor = $factor;
            } catch (\Exception) {
                // If calculation fails, use the result we got
                $conversionFactor = $this->deriveConversionFactor($quantity, $convertedQuantity);
            }
        }

        // Monitor performance
        $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        if ($duration > self::PERFORMANCE_THRESHOLD_MS) {
            Log::warning('UOM conversion exceeded performance threshold', [
                'duration_ms' => $duration,
                'from_uom' => $fromUomCode,
                'to_uom' => $toUomCode,
                'quantity' => $quantity,
            ]);
        }

        return [
            'quantity' => $convertedQuantity,
            'from_uom' => $fromUomCode,
            'to_uom' => $toUomCode,
            'conversion_factor' => $conversionFactor,
        ];
    }

    /**
     * Configure as Artisan command
     *
     * Usage: php artisan uom:convert 100 kg lb
     *
     * @param  Command  $command  Command instance
     */
    public function asCommand(Command $command): void
    {
        $quantity = $command->argument('quantity');
        $fromUom = $command->argument('from');
        $toUom = $command->argument('to');

        try {
            $result = $this->handle($quantity, $fromUom, $toUom);

            $command->info(sprintf(
                '%s %s = %s %s',
                $quantity,
                $fromUom,
                $result['quantity'],
                $toUom
            ));

            $command->line(sprintf('Conversion factor: %s', $result['conversion_factor']));
        } catch (\Exception $e) {
            $command->error('Conversion failed: '.$e->getMessage());
        }
    }

    /**
     * Get command signature
     */
    public function getCommandSignature(): string
    {
        return 'uom:convert {quantity : Quantity to convert} {from : Source UOM code} {to : Target UOM code}';
    }

    /**
     * Get command description
     */
    public function getCommandDescription(): string
    {
        return 'Convert quantity between units of measure';
    }

    /**
     * Configure as queue job
     *
     * @return string Queue name
     */
    public function asJob(): string
    {
        return 'conversions';
    }

    /**
     * Get job retry attempts
     */
    public function getJobRetries(): int
    {
        return 3;
    }

    /**
     * Validate quantity value
     *
     * @param  string  $quantity  Quantity to validate
     *
     * @throws InvalidQuantityException If quantity is invalid
     */
    protected function validateQuantity(string $quantity): void
    {
        if (! is_numeric($quantity)) {
            throw new InvalidQuantityException($quantity, 'must be numeric');
        }

        try {
            $bd = BigDecimal::of($quantity);

            if ($bd->isNegative()) {
                throw new InvalidQuantityException($quantity, 'must be positive');
            }

            if ($bd->isZero()) {
                throw new InvalidQuantityException($quantity, 'must be greater than zero');
            }
        } catch (MathException) {
            throw new InvalidQuantityException($quantity, 'invalid numeric format');
        }
    }

    /**
     * Calculate conversion factor between two UOMs
     *
     * @param  string  $fromUomCode  Source UOM code
     * @param  string  $toUomCode  Target UOM code
     * @return string Conversion factor as string
     */
    protected function calculateConversionFactor(string $fromUomCode, string $toUomCode): string
    {
        // Convert 1 unit from source to target
        $result = $this->conversionService->convert('1', $fromUomCode, $toUomCode);

        return $result;
    }

    /**
     * Derive conversion factor from quantity and result
     *
     * @param  string  $quantity  Original quantity
     * @param  string  $result  Converted quantity
     * @return string Conversion factor
     */
    protected function deriveConversionFactor(string $quantity, string $result): string
    {
        try {
            $quantityBd = BigDecimal::of($quantity);
            $resultBd = BigDecimal::of($result);

            return $resultBd->dividedBy($quantityBd, 10)->__toString();
        } catch (MathException) {
            return '1.0';
        }
    }

    /**
     * Get cache key for conversion pair
     *
     * @param  string  $fromUomCode  Source UOM code
     * @param  string  $toUomCode  Target UOM code
     * @return string Cache key
     */
    protected function getCacheKey(string $fromUomCode, string $toUomCode): string
    {
        return sprintf('uom:conversion:%s:%s', $fromUomCode, $toUomCode);
    }

    /**
     * Clear conversion cache for a UOM pair
     *
     * @param  string  $fromUomCode  Source UOM code
     * @param  string  $toUomCode  Target UOM code
     */
    public static function clearCache(string $fromUomCode, string $toUomCode): void
    {
        $cacheKey = sprintf('uom:conversion:%s:%s', $fromUomCode, $toUomCode);
        Cache::forget($cacheKey);
    }
}
