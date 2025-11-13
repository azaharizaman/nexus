<?php

declare(strict_types=1);

namespace Nexus\Erp\Actions\UnitOfMeasure;

use Nexus\Erp\Contracts\UomRepositoryContract;
use Nexus\Erp\Exceptions\UnitOfMeasure\UomNotFoundException;
use Nexus\Erp\Models\Uom;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Get Compatible UOMs Action
 *
 * Retrieves all units of measure that are compatible with a given UOM
 * (i.e., all UOMs in the same category).
 *
 * Results are cached for 1 hour to improve performance.
 */
class GetCompatibleUomsAction
{
    use \Lorisleiva\Actions\Concerns\AsAction;

    /**
     * Cache TTL in seconds (1 hour)
     */
    protected const CACHE_TTL = 3600;

    /**
     * Create a new action instance
     *
     * @param  UomRepositoryContract  $repository  UOM repository
     */
    public function __construct(
        protected readonly UomRepositoryContract $repository
    ) {}

    /**
     * Get all compatible UOMs for a given UOM
     *
     * Returns all active UOMs (system + tenant) in the same category.
     *
     * @param  Uom|string  $uom  Reference UOM (model or code)
     * @param  string|null  $tenantId  Tenant ID for scoping (optional)
     * @return Collection Collection of compatible Uom models
     *
     * @throws UomNotFoundException If UOM code is not found
     */
    public function handle(Uom|string $uom, ?string $tenantId = null): Collection
    {
        // Resolve to model if string
        $resolvedUom = $uom instanceof Uom ? $uom : $this->repository->findByCode($uom);

        if ($resolvedUom === null) {
            throw new UomNotFoundException(
                $uom instanceof Uom ? $uom->code : $uom
            );
        }

        // Build cache key with category and tenant
        $cacheKey = sprintf(
            'uom:compatible:%s:%s',
            $resolvedUom->category->value,
            $tenantId ?? 'global'
        );

        // Return cached result if available
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($resolvedUom) {
            return $this->repository->findByCategory($resolvedUom->category);
        });
    }

    /**
     * Clear cache for a specific category and tenant
     *
     * Should be called when a new UOM is created or updated in a category.
     *
     * @param  string  $category  Category value
     * @param  string|null  $tenantId  Tenant ID
     */
    public static function clearCache(string $category, ?string $tenantId = null): void
    {
        $cacheKey = sprintf(
            'uom:compatible:%s:%s',
            $category,
            $tenantId ?? 'global'
        );

        Cache::forget($cacheKey);
    }
}
