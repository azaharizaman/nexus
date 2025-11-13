<?php

declare(strict_types=1);

namespace Nexus\Erp\SettingsManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Setting Resource
 *
 * Transforms Setting model to JSON:API format.
 *
 * @mixin \Nexus\Erp\SettingsManagement\Models\Setting
 */
class SettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $canViewEncrypted = $user && $user->can(config('settings-management.permissions.view_encrypted', 'view-encrypted-settings'));

        return [
            'id' => $this->id,
            'key' => $this->key,
            'value' => $this->getDisplayValue($canViewEncrypted),
            'type' => $this->type,
            'scope' => $this->scope,
            'tenant_id' => $this->when(
                $this->shouldShowTenantId($user),
                $this->tenant_id
            ),
            'module_name' => $this->module_name,
            'user_id' => $this->user_id,
            'metadata' => $this->metadata,
            'is_encrypted' => $this->isEncrypted(),
            'is_system_level' => $this->isSystemLevel(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.settings.show', ['key' => $this->key]),
            ],
        ];
    }

    /**
     * Get display value based on permissions
     *
     * @param bool $canViewEncrypted
     * @return mixed
     */
    protected function getDisplayValue(bool $canViewEncrypted): mixed
    {
        if ($this->isEncrypted() && !$canViewEncrypted) {
            return '***ENCRYPTED***';
        }

        // The value is already decrypted by the service layer
        return $this->value;
    }

    /**
     * Determine if tenant_id should be shown
     *
     * @param mixed $user
     * @return bool
     */
    protected function shouldShowTenantId(mixed $user): bool
    {
        if (!$user) {
            return false;
        }

        // Show tenant_id if user is super admin or for non-tenant scopes
        return $user->hasRole('super-admin') || $this->scope !== 'tenant';
    }
}
