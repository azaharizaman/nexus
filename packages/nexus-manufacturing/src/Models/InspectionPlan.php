<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspectionPlan extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'manufacturing_inspection_plans';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'inspection_type',
        'sampling_plan',
    ];

    /**
     * Get the product this plan is for.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get inspection characteristics.
     */
    public function characteristics(): HasMany
    {
        return $this->hasMany(InspectionCharacteristic::class, 'inspection_plan_id')
            ->orderBy('display_order');
    }

    /**
     * Get inspections using this plan.
     */
    public function inspections(): HasMany
    {
        return $this->hasMany(QualityInspection::class, 'inspection_plan_id');
    }
}
