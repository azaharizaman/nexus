<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasHierarchy;

/**
 * Department Model
 * 
 * Represents a logical department structure that can have hierarchical relationships.
 * Departments belong to companies and create logical hierarchies.
 * 
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property int $company_id
 * @property int|null $parent_department_id
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * 
 * @property-read Company $company
 * @property-read Department|null $parentDepartment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Department> $childDepartments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Staff> $staff
 */
class Department extends Model implements DepartmentInterface
{
    use HasFactory, SoftDeletes, HasHierarchy;
use Nexus\Backoffice\Contracts\DepartmentInterface;

    /**
     * The table associated with the model.
     */
    protected $table = 'backoffice_departments';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'company_id',
        'parent_department_id',
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
     * Get the company that owns this department.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the parent department.
     */
    public function parentDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_department_id');
    }

    /**
     * Get the child departments.
     */
    public function childDepartments(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_department_id');
    }

    /**
     * Get the staff assigned to this department.
     */
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    /**
     * Get the root department (top-level parent).
     */
    public function rootDepartment(): Department
    {
        return $this->getRoot();
    }

    /**
     * Get all descendant departments.
     */
    public function allChildDepartments()
    {
        return $this->getDescendants();
    }

    /**
     * Get all ancestor departments.
     */
    public function allParentDepartments()
    {
        return $this->getAncestors();
    }

    /**
     * Check if department is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Scope to get only active departments.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get root departments (no parent).
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_department_id');
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
    protected static function newFactory(): \Nexus\Backoffice\Database\Factories\DepartmentFactory
    {
        return \Nexus\Backoffice\Database\Factories\DepartmentFactory::new();
    }
}