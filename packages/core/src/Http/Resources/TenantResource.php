<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tenant Resource
 *
 * Transforms tenant model to JSON:API format.
 * Conditionally includes sensitive data (configuration) only for admins.
 *
 * @property \Azaharizaman\Erp\Core\Models\Tenant $resource
 */
class TenantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'domain' => $this->resource->domain,
            'status' => [
                'value' => $this->resource->status->value,
                'label' => $this->resource->status->label(),
            ],
            'subscription_plan' => $this->resource->subscription_plan,
            'billing_email' => $this->resource->billing_email,
            'contact_name' => $this->resource->contact_name,
            'contact_email' => $this->resource->contact_email,
            'contact_phone' => $this->resource->contact_phone,
            'created_at' => $this->resource->created_at?->toISOString(),
            'updated_at' => $this->resource->updated_at?->toISOString(),

            // Conditionally include configuration only for admins
            $this->mergeWhen(
                $request->user()?->hasRole('admin') ?? false,
                [
                    'configuration' => $this->resource->configuration,
                ]
            ),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'links' => [
                'self' => route('api.v1.tenants.show', ['tenant' => $this->resource->id]),
            ],
        ];
    }
}
