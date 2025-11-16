<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * UnitGroup Model
 * 
 * Represents a group of units that belong to a company.
 * Unit groups organize units but units cannot be hierarchical.
 * 
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property int $company_id
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * 
 * @property-read Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Unit> $units
 */
class UnitGroup extends Model implements UnitGroupInterface
{
    use HasFactory, SoftDeletes;
use Nexus\Backoffice\Contracts\UnitGroupInterface;

    /**
     * The table associated with the model.
     */
    protected $table = 'backoffice_unit_groups';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'company_id',
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
     * Get the company that owns this unit group.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the units in this group.
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * Check if unit group is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get all staff through units.
     */
    public function getAllStaff()
    {
        return Staff::whereHas('units', function ($query) {
            $query->where('unit_group_id', $this->id);
        });
    }

    /**
     * Scope to get only active unit groups.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by company.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Nexus\Backoffice\Database\Factories\UnitGroupFactory
    {
        return \Nexus\Backoffice\Database\Factories\UnitGroupFactory::new();
    }
}