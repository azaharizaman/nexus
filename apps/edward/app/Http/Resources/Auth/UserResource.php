<?php

declare(strict_types=1);

namespace Nexus\Erp\Http\Resources\Auth;

use Nexus\Erp\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * User Resource
 *
 * Transforms User model to JSON:API format.
 *
 * @property User $resource
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenant_id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'status' => $this->resource->status->value ?? $this->resource->status,
            'is_admin' => $this->resource->is_admin,
            'mfa_enabled' => $this->resource->mfa_enabled,
            'email_verified_at' => $this->resource->email_verified_at?->toIso8601String(),
            'last_login_at' => $this->resource->last_login_at?->toIso8601String(),
            'created_at' => $this->resource->created_at->toIso8601String(),
            'updated_at' => $this->resource->updated_at->toIso8601String(),

            // Conditional fields
            'tenant' => $this->when(
                $this->resource->relationLoaded('tenant'),
                fn () => [
                    'id' => $this->resource->tenant->id,
                    'name' => $this->resource->tenant->name,
                ]
            ),

            'roles' => $this->when(
                $this->resource->relationLoaded('roles'),
                fn () => $this->resource->roles->pluck('name')
            ),

            'permissions' => $this->when(
                $this->resource->relationLoaded('permissions'),
                fn () => $this->resource->getAllPermissions()->pluck('name')
            ),

            // Links - conditionally include if route exists
            'links' => $this->when(
                \Illuminate\Support\Facades\Route::has('api.v1.users.show'),
                fn () => [
                    'self' => route('api.v1.users.show', ['user' => $this->resource->id]),
                ]
            ),
        ];
    }
}
