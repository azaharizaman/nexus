<?php

declare(strict_types=1);

namespace Nexus\Crm\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmDefinition extends Model
{
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'description',
        'schema',
        'pipeline_config',
        'permissions',
        'is_active',
    ];

    protected $casts = [
        'schema' => 'array',
        'pipeline_config' => 'array',
        'permissions' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the entities for this definition.
     */
    public function entities(): HasMany
    {
        return $this->hasMany(CrmEntity::class, 'definition_id');
    }

    /**
     * Scope to active definitions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by entity type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}