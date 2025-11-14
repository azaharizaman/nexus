<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Models;

use Nexus\Backoffice\Enums\PositionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Position model representing job positions in the organizational hierarchy.
 * 
 * @property int $id
 * @property int $company_id
 * @property int|null $department_id
 * @property string $name
 * @property string $code
 * @property string|null $gred
 * @property PositionType $type
 * @property string|null $description
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * 
 * @property-read Company $company
 * @property-read Department|null $department
 * @property-read \Illuminate\Database\Eloquent\Collection<Staff> $staff
 * 
 * @method static Builder active()
 * @method static Builder inactive()
 * @method static Builder byCompany(Company $company)
 * @method static Builder byDepartment(Department $department)
 * @method static Builder byType(PositionType $type)
 * @method static Builder management()
 * @method static Builder executive()
 */
class Position extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'backoffice_positions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id',
        'department_id',
        'name',
        'code',
        'gred',
        'type',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => PositionType::class,
        'is_active' => 'boolean',
    ];

    /**
     * Get the company that owns the position.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the default department for this position (optional).
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get all staff assigned to this position.
     */
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    /**
     * Get active staff in this position.
     */
    public function activeStaff(): HasMany
    {
        return $this->staff()->where('is_active', true);
    }

    /**
     * Scope query to only include active positions.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to only include inactive positions.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope query to positions in a specific company.
     */
    public function scopeByCompany(Builder $query, Company $company): Builder
    {
        return $query->where('company_id', $company->id);
    }

    /**
     * Scope query to positions in a specific department.
     */
    public function scopeByDepartment(Builder $query, Department $department): Builder
    {
        return $query->where('department_id', $department->id);
    }

    /**
     * Scope query to positions of a specific type.
     */
    public function scopeByType(Builder $query, PositionType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope query to management level positions.
     */
    public function scopeManagement(Builder $query): Builder
    {
        return $query->whereIn('type', [
            PositionType::C_LEVEL,
            PositionType::TOP_MANAGEMENT,
            PositionType::MANAGEMENT,
            PositionType::JUNIOR_MANAGEMENT,
        ]);
    }

    /**
     * Scope query to executive level positions.
     */
    public function scopeExecutive(Builder $query): Builder
    {
        return $query->whereIn('type', [
            PositionType::SENIOR_EXECUTIVE,
            PositionType::EXECUTIVE,
            PositionType::JUNIOR_EXECUTIVE,
        ]);
    }

    /**
     * Check if this position has a default department.
     */
    public function hasDefaultDepartment(): bool
    {
        return $this->department_id !== null;
    }

    /**
     * Get the hierarchical level of this position (1 = highest).
     */
    public function getLevel(): int
    {
        return $this->type->level();
    }

    /**
     * Check if this position is management level.
     */
    public function isManagement(): bool
    {
        return $this->type->isManagement();
    }

    /**
     * Check if this position is executive level.
     */
    public function isExecutive(): bool
    {
        return $this->type->isExecutive();
    }

    /**
     * Get the number of staff currently in this position.
     */
    public function getStaffCount(): int
    {
        return $this->staff()->count();
    }

    /**
     * Get the number of active staff in this position.
     */
    public function getActiveStaffCount(): int
    {
        return $this->activeStaff()->count();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Nexus\Backoffice\Database\Factories\PositionFactory
    {
        return \Nexus\Backoffice\Database\Factories\PositionFactory::new();
    }
}
