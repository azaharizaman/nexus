<?php

declare(strict_types=1);

namespace Nexus\Erp\SettingsManagement\Models;

use Nexus\Erp\Core\Models\Tenant;
use Nexus\Erp\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Setting History Model
 *
 * Tracks changes to settings for audit trail and compliance.
 *
 * @property int $id
 * @property int|null $setting_id
 * @property string $key
 * @property string $scope
 * @property int|null $tenant_id
 * @property string|null $module_name
 * @property int|null $user_id
 * @property string|null $old_value
 * @property string|null $new_value
 * @property string|null $old_type
 * @property string|null $new_type
 * @property string $action
 * @property int|null $changed_by
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $changed_at
 */
class SettingHistory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'settings_history';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'setting_id',
        'key',
        'scope',
        'tenant_id',
        'module_name',
        'user_id',
        'old_value',
        'new_value',
        'old_type',
        'new_type',
        'action',
        'changed_by',
        'ip_address',
        'user_agent',
        'changed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'changed_at' => 'datetime',
        'setting_id' => 'integer',
        'tenant_id' => 'integer',
        'user_id' => 'integer',
        'changed_by' => 'integer',
    ];

    /**
     * Available actions
     */
    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DELETED = 'deleted';

    /**
     * Get the setting that this history entry belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function setting(): BelongsTo
    {
        return $this->belongsTo(Setting::class);
    }

    /**
     * Get the tenant associated with this history entry.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who changed the setting.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Scope a query to only include history for a specific setting.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $settingId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForSetting($query, int $settingId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('setting_id', $settingId);
    }

    /**
     * Scope a query to only include history for a specific key.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $key
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForKey($query, string $key): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('key', $key);
    }

    /**
     * Scope a query to only include history for a specific action.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $action
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfAction($query, string $action): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('action', $action);
    }
}
