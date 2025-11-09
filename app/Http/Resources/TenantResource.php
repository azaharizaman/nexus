<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tenant Resource
 *
 * Transforms tenant model into JSON:API compliant response.
 *
 * @property \App\Domains\Core\Models\Tenant $resource
 */
class TenantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request  The HTTP request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'domain' => $this->resource->domain,
            'status' => $this->resource->status->value,
            'status_label' => $this->resource->status->label(),
            'configuration' => $this->when(
                $request->user()?->isAdmin(),
                $this->resource->configuration
            ),
            'subscription_plan' => $this->resource->subscription_plan,
            'billing_email' => $this->resource->billing_email,
            'contact_name' => $this->resource->contact_name,
            'contact_email' => $this->resource->contact_email,
            'contact_phone' => $this->resource->contact_phone,
            'is_active' => $this->resource->isActive(),
            'is_suspended' => $this->resource->isSuspended(),
            'is_archived' => $this->resource->isArchived(),
            'created_at' => $this->resource->created_at?->toISOString(),
            'updated_at' => $this->resource->updated_at?->toISOString(),
            'deleted_at' => $this->resource->deleted_at?->toISOString(),
            'users_count' => $this->when(
                isset($this->resource->users_count),
                fn () => $this->resource->users_count
            ),
            'links' => [
                'self' => route('api.v1.tenants.show', ['tenant' => $this->resource->id]),
            ],
        ];
    }
}
