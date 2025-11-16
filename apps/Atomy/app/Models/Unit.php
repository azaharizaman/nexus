<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Unit Model
 * 
 * Represents a logical grouping of staff.
 * Units belong to unit groups but cannot be hierarchical.
 * Staff can belong to multiple units.
 * 
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property int $unit_group_id
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * 
 * @property-read UnitGroup $unitGroup
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Staff> $staff
 */
class Unit extends Model implements UnitInterface
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'backoffice_units';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'unit_group_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the unit group that owns this unit.
     */
    public function unitGroup(): BelongsTo
    {
        return $this->belongsTo(UnitGroup::class);
    }

    /**
     * Get the staff that belong to this unit.
     */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(
            Staff::class,
            'backoffice_staff_unit',
            'unit_id',
            'staff_id'
        );
    }

    /**
     * Check if unit is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get the company through the unit group.
     */
    public function getCompany(): ?Company
    {
        return $this->unitGroup?->company;
    }

    /**
     * Scope to get only active units.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by unit group.
     */
    public function scopeInGroup($query, $unitGroupId)
    {
        return $query->where('unit_group_id', $unitGroupId);
    }

    /**
     * Scope to filter by company through unit group.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->whereHas('unitGroup', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
use Nexus\Backoffice\Contracts\UnitInterface;
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Nexus\Backoffice\Database\Factories\UnitFactory
    {
        return \Nexus\Backoffice\Database\Factories\UnitFactory::new();
    }
}