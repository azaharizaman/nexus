<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Staff;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasHierarchy;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Nexus\Backoffice\Contracts\CompanyInterface;

/**
 * Company Model
 * 
 * Represents a company entity that can have parent-child relationships.
 * One parent company can have many child companies.
 * 
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property int|null $parent_company_id
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * 
 * @property-read Company|null $parentCompany
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Company> $childCompanies
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Office> $offices
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Department> $departments
 */
class Company extends Model implements CompanyInterface
{
    use HasFactory, SoftDeletes, HasHierarchy;

    /**
     * The table associated with the model.
     */
    protected $table = 'backoffice_companies';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'parent_company_id',
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
     * Get the parent company.
     */
    public function parentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_company_id');
    }

    /**
     * Get the child companies.
     */
    public function childCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_company_id');
    }

    /**
     * Get the offices for this company.
     */
    public function offices(): HasMany
    {
        return $this->hasMany(Office::class);
    }

    /**
     * Get the departments for this company.
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    /**
     * Get the root company (top-level parent).
     */
    public function rootCompany(): Company
    {
        return $this->getRoot();
    }

    /**
     * Get all descendant companies.
     */
    public function allChildCompanies()
    {
        return $this->getDescendants();
    }

    /**
     * Get all ancestor companies.
     */
    public function allParentCompanies()
    {
        return $this->getAncestors();
    }

    /**
     * Check if company is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    // Interface method implementations
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getParentCompanyId(): ?int
    {
        return $this->parent_company_id;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deleted_at;
    }

    /**
     * Scope to get only active companies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get root companies (no parent).
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_company_id');
    }

    /**
     * Get organizational chart for this company.
     */
    public function getOrganizationalChart(): array
    {
        return \Nexus\Backoffice\Helpers\OrganizationalChart::forCompany($this);
    }

    /**
     * Get organizational statistics for this company.
     */
    public function getOrganizationalStatistics(): array
    {
        return \Nexus\Backoffice\Helpers\OrganizationalChart::statistics($this);
    }

    /**
     * Get all staff that belong to this company (via offices or departments).
     *
     * Returns a collection of Staff models with common relationships eager loaded.
     */
    public function getAllStaff()
    {
        $query = Staff::query()
            ->with(['supervisor', 'subordinates', 'office', 'department'])
            ->where(function ($q) {
                $q->whereHas('office', function ($q2) {
                    $q2->where('company_id', $this->id);
                })->orWhereHas('department', function ($q2) {
                    $q2->where('company_id', $this->id);
                });
            });

        return $query->get()->unique('id')->values();
    }

    /**
     * Get top-level staff for this company (staff without supervisors).
     */
    public function getTopLevelStaff()
    {
        return $this->getAllStaff()->filter(fn (Staff $s) => $s->isTopLevel())->values();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Nexus\Backoffice\Database\Factories\CompanyFactory
    {
        return \Nexus\Backoffice\Database\Factories\CompanyFactory::new();
    }
}