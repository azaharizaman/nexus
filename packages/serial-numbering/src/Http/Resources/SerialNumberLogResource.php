<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SerialNumbering\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serial Number Log Resource
 *
 * Transforms SerialNumberLog model to JSON:API format.
 *
 * @property \Azaharizaman\Erp\SerialNumbering\Models\SerialNumberLog $resource
 */
class SerialNumberLogResource extends JsonResource
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
            'sequence_name' => $this->resource->sequence_name,
            'generated_number' => $this->resource->generated_number,
            'causer' => $this->when(
                $this->resource->causer !== null,
                fn () => [
                    'id' => $this->resource->causer_id,
                    'type' => class_basename($this->resource->causer_type),
                ]
            ),
            'metadata' => $this->resource->metadata,
            'created_at' => $this->resource->created_at->toISOString(),
        ];
    }
}
