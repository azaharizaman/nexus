<?php

declare(strict_types=1);

namespace Nexus\Crm\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmPipeline extends Model
{
    use HasUlids;
    protected $fillable = [
        'name',
        'description',
        'entity_type',
        'config',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Get the stages for this pipeline.
     */
    public function stages(): HasMany
    {
        return $this->hasMany(CrmStage::class, 'pipeline_id')->orderBy('order');
    }

    /**
     * Scope to active pipelines.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to default pipelines.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope by entity type.
     */
    public function scopeForEntityType($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Get the default pipeline for an entity type.
     */
    public static function getDefaultForType(string $entityType): ?self
    {
        return static::forEntityType($entityType)->default()->active()->first();
    }
}