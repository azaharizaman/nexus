<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Manufacturing\Enums\WorkOrderStatus;

class WorkOrder extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'manufacturing_work_orders';

    protected $fillable = [
        'tenant_id',
        'work_order_number',
        'product_id',
        'bom_id',
        'routing_id',
        'quantity_ordered',
        'quantity_completed',
        'quantity_scrapped',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
        'status',
        'priority',
        'source_type',
        'source_reference',
        'created_by',
    ];

    protected $casts = [
        'status' => WorkOrderStatus::class,
        'quantity_ordered' => 'decimal:6',
        'quantity_completed' => 'decimal:6',
        'quantity_scrapped' => 'decimal:6',
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
    ];

    /**
     * Get the product to produce.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the BOM being used.
     */
    public function billOfMaterial(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bom_id');
    }

    /**
     * Get the routing being used.
     */
    public function routing(): BelongsTo
    {
        return $this->belongsTo(Routing::class, 'routing_id');
    }

    /**
     * Get material allocations for this work order.
     */
    public function materialAllocations(): HasMany
    {
        return $this->hasMany(MaterialAllocation::class, 'work_order_id');
    }

    /**
     * Get production reports for this work order.
     */
    public function productionReports(): HasMany
    {
        return $this->hasMany(ProductionReport::class, 'work_order_id');
    }

    /**
     * Get operation logs for this work order.
     */
    public function operationLogs(): HasMany
    {
        return $this->hasMany(OperationLog::class, 'work_order_id');
    }

    /**
     * Get quality inspections for this work order.
     */
    public function qualityInspections(): HasMany
    {
        return $this->hasMany(QualityInspection::class, 'work_order_id');
    }

    /**
     * Check if work order can be released.
     */
    public function canRelease(): bool
    {
        return $this->status->canRelease();
    }

    /**
     * Check if work order can start production.
     */
    public function canStartProduction(): bool
    {
        return $this->status->canStartProduction();
    }

    /**
     * Check if work order can be paused.
     */
    public function canPause(): bool
    {
        return $this->status->canPause();
    }

    /**
     * Check if work order can be resumed.
     */
    public function canResume(): bool
    {
        return $this->status->canResume();
    }

    /**
     * Check if work order can be completed.
     */
    public function canComplete(): bool
    {
        if (!$this->status->canComplete()) {
            return false;
        }

        // Check if total production matches ordered quantity
        $totalProduced = $this->quantity_completed + $this->quantity_scrapped;
        return $totalProduced >= $this->quantity_ordered;
    }

    /**
     * Check if work order can be cancelled.
     */
    public function canCancel(): bool
    {
        return $this->status->canCancel();
    }

    /**
     * Get remaining quantity to produce.
     */
    public function getRemainingQuantity(): float
    {
        $totalProduced = $this->quantity_completed + $this->quantity_scrapped;
        return max(0, $this->quantity_ordered - $totalProduced);
    }

    /**
     * Get completion percentage.
     */
    public function getCompletionPercentage(): float
    {
        if ($this->quantity_ordered == 0) {
            return 0;
        }

        $totalProduced = $this->quantity_completed + $this->quantity_scrapped;
        return min(100, ($totalProduced / $this->quantity_ordered) * 100);
    }

    /**
     * Check if work order is overdue.
     */
    public function isOverdue(): bool
    {
        if (!$this->planned_end_date || $this->status->isClosed()) {
            return false;
        }

        return now()->isAfter($this->planned_end_date);
    }
}
