<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\AuditLogging\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Audit Log Resource
 *
 * Transforms Activity model to JSON:API format for API responses.
 */
class AuditLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request  The HTTP request
     * @return array<string, mixed> Transformed data
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // Only include tenant_id if explicitly requested or user is super-admin
            // Authorization should be handled at controller/policy level
            'tenant_id' => $this->tenant_id,
            'log_name' => $this->log_name,
            'description' => $this->description,
            'event' => $this->event,
            'subject' => [
                'type' => $this->subject_type,
                'id' => $this->subject_id,
                'model' => $this->whenLoaded('subject', function () {
                    return class_basename($this->subject_type);
                }),
            ],
            'causer' => [
                'type' => $this->causer_type,
                'id' => $this->causer_id,
                'name' => $this->whenLoaded('causer', function () {
                    if ($this->causer) {
                        return method_exists($this->causer, 'getName')
                            ? $this->causer->getName()
                            : ($this->causer->name ?? 'Unknown');
                    }

                    return 'System';
                }),
            ],
            'properties' => $this->properties ?? [],
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'request_id' => $this->request_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'links' => [
                'self' => route('api.v1.audit-logs.show', $this->id),
            ],
        ];
    }
}
