<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Routing extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'manufacturing_routings';

    protected $fillable = [
        'tenant_id',
        'bom_id',
        'routing_version',
        'status',
    ];

    /**
     * Get the parent BOM.
     */
    public function billOfMaterial(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bom_id');
    }

    /**
     * Get the operations for this routing.
     */
    public function operations(): HasMany
    {
        return $this->hasMany(RoutingOperation::class, 'routing_id')->orderBy('operation_number');
    }

    /**
     * Get total setup time for all operations.
     */
    public function getTotalSetupTime(): float
    {
        return $this->operations()->sum('setup_time_minutes');
    }

    /**
     * Get total run time per unit.
     */
    public function getTotalRunTimePerUnit(): float
    {
        return $this->operations()->sum('run_time_per_unit_minutes');
    }

    /**
     * Calculate total time for a given quantity.
     */
    public function calculateTotalTime(float $quantity): float
    {
        $setupTime = $this->getTotalSetupTime();
        $runTime = $this->getTotalRunTimePerUnit() * $quantity;
        $moveTime = $this->operations()->sum('move_time_minutes');

        return $setupTime + $runTime + $moveTime;
    }
}
