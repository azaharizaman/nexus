<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialAllocation extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'manufacturing_material_allocations';

    protected $fillable = [
        'work_order_id',
        'component_product_id',
        'quantity_required',
        'quantity_issued',
        'quantity_consumed',
        'lot_number',
        'issued_from_location',
        'issued_at',
        'issued_by',
    ];

    protected $casts = [
        'quantity_required' => 'decimal:6',
        'quantity_issued' => 'decimal:6',
        'quantity_consumed' => 'decimal:6',
        'issued_at' => 'datetime',
    ];

    /**
     * Get the parent work order.
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    /**
     * Get the component product.
     */
    public function componentProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }

    /**
     * Check if material is fully issued.
     */
    public function isFullyIssued(): bool
    {
        return $this->quantity_issued >= $this->quantity_required;
    }

    /**
     * Check if material is fully consumed.
     */
    public function isFullyConsumed(): bool
    {
        return $this->quantity_consumed >= $this->quantity_required;
    }

    /**
     * Get remaining quantity to be issued.
     */
    public function getRemainingToIssue(): float
    {
        return max(0, $this->quantity_required - $this->quantity_issued);
    }

    /**
     * Get remaining quantity to be consumed.
     */
    public function getRemainingToConsume(): float
    {
        return max(0, $this->quantity_issued - $this->quantity_consumed);
    }

    /**
     * Get material variance (over/under consumption).
     */
    public function getVariance(): float
    {
        return $this->quantity_consumed - $this->quantity_required;
    }

    /**
     * Get variance percentage.
     */
    public function getVariancePercentage(): float
    {
        if ($this->quantity_required == 0) {
            return 0;
        }

        return ($this->getVariance() / $this->quantity_required) * 100;
    }
}
