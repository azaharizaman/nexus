<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SettingsManagement\Models;

use Azaharizaman\Erp\Core\Models\Tenant;
use Azaharizaman\Erp\Core\Models\User;
use Azaharizaman\Erp\Core\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Setting Model
 *
 * Represents a configuration setting with hierarchical scope support.
 * Supports system, tenant, module, and user-level settings with type safety.
 *
 * @property int $id
 * @property string $key
 * @property mixed $value
 * @property string $type
 * @property string $scope
 * @property int|null $tenant_id
 * @property string|null $module_name
 * @property int|null $user_id
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Setting extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use LogsActivity;
    use Searchable;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'scope',
        'tenant_id',
        'module_name',
        'user_id',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * Available setting scopes
     */
    public const SCOPE_SYSTEM = 'system';
    public const SCOPE_TENANT = 'tenant';
    public const SCOPE_MODULE = 'module';
    public const SCOPE_USER = 'user';

    /**
     * Available setting types
     */
    public const TYPE_STRING = 'string';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_ARRAY = 'array';
    public const TYPE_JSON = 'json';
    public const TYPE_ENCRYPTED = 'encrypted';

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'scope' => $this->scope,
            'module_name' => $this->module_name,
            'tenant_id' => $this->tenant_id,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Get the name of the index associated with the model.
     *
     * @return string
     */
    public function searchableAs(): string
    {
        return config('settings-management.scout.index_name', 'settings');
    }

    /**
     * Get the activity log options for the model.
     *
     * @return \Spatie\Activitylog\LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['key', 'value', 'type', 'scope', 'metadata'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the tenant that owns the setting.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user that owns the setting.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the history records for the setting.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function history(): HasMany
    {
        return $this->hasMany(SettingHistory::class);
    }

    /**
     * Scope a query to only include settings of a given scope.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $scope
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfScope($query, string $scope)
    {
        return $query->where('scope', $scope);
    }

    /**
     * Scope a query to only include settings for a specific module.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $moduleName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForModule($query, string $moduleName)
    {
        return $query->where('module_name', $moduleName);
    }

    /**
     * Scope a query to only include settings for a specific user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if the setting is system-level.
     *
     * @return bool
     */
    public function isSystemLevel(): bool
    {
        return $this->scope === self::SCOPE_SYSTEM;
    }

    /**
     * Check if the setting is tenant-level.
     *
     * @return bool
     */
    public function isTenantLevel(): bool
    {
        return $this->scope === self::SCOPE_TENANT;
    }

    /**
     * Check if the setting is module-level.
     *
     * @return bool
     */
    public function isModuleLevel(): bool
    {
        return $this->scope === self::SCOPE_MODULE;
    }

    /**
     * Check if the setting is user-level.
     *
     * @return bool
     */
    public function isUserLevel(): bool
    {
        return $this->scope === self::SCOPE_USER;
    }

    /**
     * Check if the setting value is encrypted.
     *
     * @return bool
     */
    public function isEncrypted(): bool
    {
        return $this->type === self::TYPE_ENCRYPTED;
    }
}
