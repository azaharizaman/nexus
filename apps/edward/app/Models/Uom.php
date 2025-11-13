<?php

declare(strict_types=1);

namespace Nexus\Erp\Models;

use Nexus\Erp\Enums\UomCategory;
use Nexus\Erp\Support\Traits\HasActivityLogging;
use Nexus\TenancyManagement\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Unit of Measure (UOM) Model
 *
 * Represents a unit of measure in the system (e.g., meter, kilogram, liter).
 * Supports tenant-specific custom UOMs and system-wide standard UOMs.
 *
 * @property string $id
 * @property string|null $tenant_id
 * @property string $code Unique UOM code (e.g., 'm', 'kg', 'L')
 * @property string $name Full name (e.g., 'meter', 'kilogram', 'liter')
 * @property string $symbol Display symbol (e.g., 'm', 'kg', 'L')
 * @property UomCategory $category Category enum (LENGTH, MASS, VOLUME, AREA, COUNT, TIME)
 * @property string $conversion_factor Decimal conversion factor to base unit (20,10 precision)
 * @property bool $is_system True for system UOMs, false for custom
 * @property bool $is_active Soft activation flag for UI filtering
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @method static Builder active() Query scope for active UOMs
 * @method static Builder inactive() Query scope for inactive UOMs
 * @method static Builder system() Query scope for system UOMs
 * @method static Builder custom() Query scope for custom UOMs
 * @method static Builder category(UomCategory $category) Query scope by category
 * @method static \Illuminate\Database\Eloquent\Builder withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder onlyTrashed()
 */
class Uom extends Model
{
    use BelongsToTenant;
    use HasActivityLogging;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'uoms';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'symbol',
        'category',
        'conversion_factor',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'category' => UomCategory::class,
        'conversion_factor' => 'string', // Store as string to preserve precision
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Configure activity logging for this model
     *
     * @return array<string, mixed>
     */
    protected function configureActivityLogging(): array
    {
        return [
            'log_name' => 'uom',
            'log_attributes' => ['code', 'name', 'category', 'conversion_factor', 'is_active'],
            'log_only_dirty' => true,
            'dont_submit_empty_logs' => true,
        ];
    }

    /**
     * Query scope: Active UOMs only
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Query scope: Inactive UOMs only
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Query scope: System UOMs only (standard units)
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    /**
     * Query scope: Custom UOMs only (tenant-specific)
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeCustom(Builder $query): Builder
    {
        return $query->where('is_system', false);
    }

    /**
     * Query scope: Filter by category
     *
     * @param  Builder  $query
     * @param  UomCategory  $category
     * @return Builder
     */
    public function scopeCategory(Builder $query, UomCategory $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Check if this is a base unit (conversion_factor = 1)
     *
     * Base units have a conversion factor of 1.0, meaning they don't need
     * conversion within their category.
     *
     * @return bool
     */
    public function isBaseUnit(): bool
    {
        return bccomp($this->conversion_factor, '1', 10) === 0;
    }


}
