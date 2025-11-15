<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Manufacturing\Enums\InspectionResult;
use Nexus\Manufacturing\Enums\DispositionType;

class QualityInspection extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'manufacturing_quality_inspections';

    protected $fillable = [
        'tenant_id',
        'work_order_id',
        'inspection_plan_id',
        'inspector_id',
        'inspection_date',
        'lot_number',
        'sample_size',
        'result',
        'disposition',
        'notes',
    ];

    protected $casts = [
        'inspection_date' => 'datetime',
        'sample_size' => 'integer',
        'result' => InspectionResult::class,
        'disposition' => DispositionType::class,
    ];

    /**
     * Get the parent work order.
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    /**
     * Get the inspection plan used.
     */
    public function inspectionPlan(): BelongsTo
    {
        return $this->belongsTo(InspectionPlan::class, 'inspection_plan_id');
    }

    /**
     * Get measurement results.
     */
    public function measurements(): HasMany
    {
        return $this->hasMany(InspectionMeasurement::class, 'quality_inspection_id');
    }

    /**
     * Check if inspection passed.
     */
    public function isPassed(): bool
    {
        return $this->result === InspectionResult::PASSED;
    }

    /**
     * Check if inspection failed.
     */
    public function isFailed(): bool
    {
        return $this->result === InspectionResult::FAILED;
    }

    /**
     * Check if inspection result is acceptable.
     */
    public function isAcceptable(): bool
    {
        return $this->result->isAcceptable();
    }

    /**
     * Check if material can be used.
     */
    public function canBeUsed(): bool
    {
        return $this->disposition && $this->disposition->allowsUsage();
    }

    /**
     * Check if material is quarantined.
     */
    public function isQuarantined(): bool
    {
        return $this->disposition === DispositionType::QUARANTINE;
    }
}
