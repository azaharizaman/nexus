<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionReport extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'manufacturing_production_reports';

    protected $fillable = [
        'work_order_id',
        'operation_id',
        'reported_by',
        'report_date',
        'shift',
        'quantity_completed',
        'quantity_scrapped',
        'scrap_reason',
        'labor_hours',
        'notes',
    ];

    protected $casts = [
        'report_date' => 'date',
        'quantity_completed' => 'decimal:6',
        'quantity_scrapped' => 'decimal:6',
        'labor_hours' => 'decimal:2',
    ];

    /**
     * Get the parent work order.
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    /**
     * Get the operation (if operation-based reporting).
     */
    public function operation(): BelongsTo
    {
        return $this->belongsTo(RoutingOperation::class, 'operation_id');
    }

    /**
     * Get total quantity reported (good + scrap).
     */
    public function getTotalQuantity(): float
    {
        return $this->quantity_completed + $this->quantity_scrapped;
    }

    /**
     * Get scrap percentage.
     */
    public function getScrapPercentage(): float
    {
        $total = $this->getTotalQuantity();
        
        if ($total == 0) {
            return 0;
        }

        return ($this->quantity_scrapped / $total) * 100;
    }

    /**
     * Check if this report has acceptable scrap rate.
     */
    public function hasAcceptableScrapRate(float $maxScrapPct = 5.0): bool
    {
        return $this->getScrapPercentage() <= $maxScrapPct;
    }
}
