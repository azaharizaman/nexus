<?php

declare(strict_types=1);

namespace Nexus\Erp\Actions\UnitOfMeasure;

use Nexus\Erp\Contracts\UomRepositoryContract;
use Nexus\Erp\Models\Uom;

/**
 * Validate UOM Compatibility Action
 *
 * Checks if two units of measure are compatible (belong to same category)
 * and can be converted between each other.
 *
 * Returns boolean without throwing exceptions for safe validation checks.
 */
class ValidateUomCompatibilityAction
{
    use \Lorisleiva\Actions\Concerns\AsAction;

    /**
     * Create a new action instance
     *
     * @param  UomRepositoryContract  $repository  UOM repository
     */
    public function __construct(
        protected readonly UomRepositoryContract $repository
    ) {}

    /**
     * Validate if two UOMs are compatible for conversion
     *
     * @param  Uom|string  $uom1  First UOM (model or code)
     * @param  Uom|string  $uom2  Second UOM (model or code)
     * @return bool True if UOMs are in same category, false otherwise
     */
    public function handle(Uom|string $uom1, Uom|string $uom2): bool
    {
        try {
            // Resolve to models if strings
            $resolvedUom1 = $uom1 instanceof Uom ? $uom1 : $this->repository->findByCode($uom1);
            $resolvedUom2 = $uom2 instanceof Uom ? $uom2 : $this->repository->findByCode($uom2);

            // If either UOM not found, they're not compatible
            if ($resolvedUom1 === null || $resolvedUom2 === null) {
                return false;
            }

            // Check if categories match
            return $resolvedUom1->category === $resolvedUom2->category;
        } catch (\Exception) {
            // Any exception means incompatible
            return false;
        }
    }

    /**
     * Validation rules for Laravel validation integration
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'uom1' => ['required', 'exists:uoms,code'],
            'uom2' => ['required', 'exists:uoms,code'],
        ];
    }
}
