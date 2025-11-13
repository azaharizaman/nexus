<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\Traits\HasActivityLogging;
use App\Support\Traits\HasPermissions;
use App\Support\Traits\HasTokens;
use App\Support\Traits\IsSearchable;
use App\Enums\UserStatus;
use Nexus\TenancyManagement\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use BelongsToTenant;
    use HasActivityLogging;
    use HasFactory;
    use HasPermissions;
    use HasTokens;
    use HasUuids;
    use IsSearchable;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'status',
        'email_verified_at',
        'last_login_at',
        'mfa_enabled',
        'mfa_secret',
        'failed_login_attempts',
        'locked_until',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'mfa_enabled' => 'boolean',
            'mfa_secret' => 'encrypted',
            'failed_login_attempts' => 'integer',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Check if the user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    /**
     * Check if the user account is active.
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }

    /**
     * Check if the user account is locked.
     *
     * Checks both permanent lock status and temporary lockout timestamp.
     */
    public function isLocked(): bool
    {
        if ($this->status === UserStatus::LOCKED) {
            return true;
        }

        // Check temporary lockout
        if ($this->locked_until && $this->locked_until->isFuture()) {
            return true;
        }

        return false;
    }

    /**
     * Check if MFA is enabled for the user.
     */
    public function hasMfaEnabled(): bool
    {
        return $this->mfa_enabled === true && ! empty($this->mfa_secret);
    }

    /**
     * Increment failed login attempts.
     *
     * Automatically locks the account for 30 minutes after 5 failed attempts.
     */
    public function incrementFailedLoginAttempts(): void
    {
        $this->failed_login_attempts++;

        // Lock account after 5 failed attempts
        if ($this->failed_login_attempts >= 5) {
            $this->locked_until = now()->addMinutes(30);
        }

        $this->save();
    }

    /**
     * Reset failed login attempts.
     *
     * Clears the failed login counter and removes any temporary lockout.
     */
    public function resetFailedLoginAttempts(): void
    {
        $this->failed_login_attempts = 0;
        $this->locked_until = null;
        $this->save();
    }

    /**
     * Update last login timestamp.
     */
    public function updateLastLogin(): void
    {
        $this->last_login_at = now();
        $this->save();
    }

    /**
     * Configure activity logging for this model.
     *
     * @return array<string, mixed>
     */
    protected function configureActivityLogging(): array
    {
        return [
            'log_name' => 'users',
            'log_attributes' => [
                'name',
                'email',
                'status',
                'tenant_id',
                'is_admin',
                'mfa_enabled',
                'last_login_at',
            ],
            'log_only_dirty' => true,
            'dont_submit_empty_logs' => true,
        ];
    }

    /**
     * Configure search behavior for this model.
     *
     * @return array<string, mixed>
     */
    protected function configureSearchable(): array
    {
        return [
            'index_name' => 'users',
            'searchable_fields' => [
                'id',
                'name',
                'email',
                'status',
                'tenant_id',
                'is_admin',
                'created_at',
            ],
        ];
    }

    /**
     * Get the team ID for permission scoping.
     *
     * This method is used by Spatie Permission to scope roles and permissions
     * to the user's tenant, ensuring multi-tenant isolation.
     *
     * @return int|string|null The tenant ID for permission scoping
     */
    public function getPermissionTeamId(): int|string|null
    {
        return $this->tenant_id;
    }
}
