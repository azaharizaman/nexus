<?php

declare(strict_types=1);

namespace Nexus\Crm\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmStage extends Model
{
    use HasUlids;
    protected $fillable = [
        'name',
        'description',
        'color',
        'pipeline_id',
        'order',
        'config',
        'is_active',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the pipeline for this stage.
     */
    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(CrmPipeline::class, 'pipeline_id');
    }

    /**
     * Get entities currently in this stage.
     */
    public function entities(): HasMany
    {
        return $this->hasMany(CrmEntity::class, 'current_stage_id');
    }

    /**
     * Scope to active stages.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by pipeline.
     */
    public function scopeInPipeline($query, string $pipelineId)
    {
        return $query->where('pipeline_id', $pipelineId);
    }

    /**
     * Scope ordered by order field.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}