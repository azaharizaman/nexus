<?php

declare(strict_types=1);

namespace Nexus\Erp\SettingsManagement\Policies;

use Nexus\Erp\Core\Models\User;
use Nexus\Erp\SettingsManagement\Models\Setting;

/**
 * Setting Policy
 *
 * Handles authorization for setting operations based on scope and user permissions.
 */
class SettingPolicy
{
    /**
     * Determine whether the user can view any settings.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // Any authenticated user can view settings
        return true;
    }

    /**
     * Determine whether the user can view the setting.
     *
     * @param User $user
     * @param Setting $setting
     * @return bool
     */
    public function view(User $user, Setting $setting): bool
    {
        // Super admins can view any setting
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // System settings are viewable by admins
        if ($setting->isSystemLevel() && $user->hasRole('admin')) {
            return true;
        }

        // Tenant settings must match user's tenant
        if ($setting->isTenantLevel()) {
            return $setting->tenant_id === $user->tenant_id;
        }

        // Module settings must match user's tenant
        if ($setting->isModuleLevel()) {
            return $setting->tenant_id === $user->tenant_id;
        }

        // User settings must match the user
        if ($setting->isUserLevel()) {
            return $setting->user_id === $user->id && $setting->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create settings.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        // Super admins can create any setting
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Admins can create tenant/module/user settings
        if ($user->hasRole('admin')) {
            return true;
        }

        // Regular users can create their own user-level settings
        return true;
    }

    /**
     * Determine whether the user can update the setting.
     *
     * @param User $user
     * @param Setting $setting
     * @return bool
     */
    public function update(User $user, Setting $setting): bool
    {
        // Super admins can update any setting
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Only super admins can modify system settings
        if ($setting->isSystemLevel()) {
            return false;
        }

        // Admins can update tenant and module settings for their tenant
        if ($user->hasRole('admin') && ($setting->isTenantLevel() || $setting->isModuleLevel())) {
            return $setting->tenant_id === $user->tenant_id;
        }

        // Users can update their own user-level settings
        if ($setting->isUserLevel()) {
            return $setting->user_id === $user->id && $setting->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the setting.
     *
     * @param User $user
     * @param Setting $setting
     * @return bool
     */
    public function delete(User $user, Setting $setting): bool
    {
        // Super admins can delete any setting
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Only super admins can delete system settings
        if ($setting->isSystemLevel()) {
            return false;
        }

        // Admins can delete tenant and module settings for their tenant
        if ($user->hasRole('admin') && ($setting->isTenantLevel() || $setting->isModuleLevel())) {
            return $setting->tenant_id === $user->tenant_id;
        }

        // Users can delete their own user-level settings
        if ($setting->isUserLevel()) {
            return $setting->user_id === $user->id && $setting->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can export settings.
     *
     * @param User $user
     * @return bool
     */
    public function export(User $user): bool
    {
        // Super admins and admins can export
        return $user->hasRole('super-admin') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can import settings.
     *
     * @param User $user
     * @return bool
     */
    public function import(User $user): bool
    {
        // Super admins and admins can import
        return $user->hasRole('super-admin') || $user->hasRole('admin');
    }
}
