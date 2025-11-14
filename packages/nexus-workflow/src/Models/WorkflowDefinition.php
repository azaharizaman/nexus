<?php

declare(strict_types=1);

namespace Nexus\Workflow\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * WorkflowDefinition Model
 * 
 * Stores database-driven workflow definitions (Level 2).
 * Supports versioning, activation, and JSON import/export.
 * 
 * @property string $id
 * @property string $name
 * @property string $key
 * @property string|null $description
 * @property int $version
 * @property array $definition
 * @property bool $is_active
 * @property string|null $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class WorkflowDefinition extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'workflow_definitions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'key',
        'description',
        'version',
        'definition',
        'is_active',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'definition' => 'array',
        'is_active' => 'boolean',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the workflow instances using this definition.
     */
    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class);
    }

    /**
     * Get the approver groups for this workflow.
     */
    public function approverGroups(): HasMany
    {
        return $this->hasMany(ApproverGroup::class);
    }

    /**
     * Scope to get only active workflow definitions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get workflows by key.
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    /**
     * Get the latest version for a given workflow key.
     */
    public static function getLatestVersion(string $key): ?self
    {
        return static::byKey($key)
            ->orderBy('version', 'desc')
            ->first();
    }

    /**
     * Get the active version for a given workflow key.
     */
    public static function getActiveVersion(string $key): ?self
    {
        return static::byKey($key)
            ->active()
            ->first();
    }

    /**
     * Activate this workflow definition and deactivate others with same key.
     */
    public function activate(): bool
    {
        return \DB::transaction(function () {
            // Deactivate all other versions
            static::byKey($this->key)
                ->where('id', '!=', $this->id)
                ->update(['is_active' => false]);

            // Activate this version
            $this->is_active = true;
            return $this->save();
        });
    }

    /**
     * Create a new version of this workflow.
     */
    public function createNewVersion(array $definition, ?string $createdBy = null): self
    {
        $latestVersion = static::getLatestVersion($this->key);
        $newVersion = ($latestVersion?->version ?? 0) + 1;

        return static::create([
            'name' => $this->name,
            'key' => $this->key,
            'description' => $this->description,
            'version' => $newVersion,
            'definition' => $definition,
            'is_active' => false,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Export workflow definition to JSON.
     */
    public function toJson($options = 0): string
    {
        return json_encode([
            'name' => $this->name,
            'key' => $this->key,
            'description' => $this->description,
            'version' => $this->version,
            'definition' => $this->definition,
        ], $options | JSON_PRETTY_PRINT);
    }

    /**
     * Import workflow definition from JSON.
     */
    public static function fromJson(string $json, ?string $createdBy = null): self
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }

        return static::create([
            'name' => $data['name'],
            'key' => $data['key'],
            'description' => $data['description'] ?? null,
            'version' => $data['version'] ?? 1,
            'definition' => $data['definition'],
            'is_active' => false,
            'created_by' => $createdBy,
        ]);
    }
}
