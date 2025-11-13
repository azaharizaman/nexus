<?php

declare(strict_types=1);

namespace Nexus\Erp\SerialNumbering\Http\Resources;

use Nexus\Erp\SerialNumbering\Actions\PreviewSerialNumberAction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Sequence Resource
 *
 * Transforms Sequence model to JSON:API format.
 *
 * @property \Nexus\Erp\SerialNumbering\Models\Sequence $resource
 */
class SequenceResource extends JsonResource
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
            'pattern' => $this->resource->pattern,
            'reset_period' => $this->resource->reset_period->value,
            'padding' => $this->resource->padding,
            'current_value' => $this->resource->current_value,
            'last_reset_at' => $this->resource->last_reset_at?->toISOString(),
            'preview' => $this->when(
                $request->boolean('include_preview'),
                fn () => $this->generatePreview()
            ),
            'metadata' => $this->resource->metadata,
            'created_at' => $this->resource->created_at->toISOString(),
            'updated_at' => $this->resource->updated_at->toISOString(),
        ];
    }

    /**
     * Generate a preview of the next serial number.
     *
     * @return string|null
     */
    private function generatePreview(): ?string
    {
        try {
            return PreviewSerialNumberAction::run(
                $this->resource->tenant_id,
                $this->resource->sequence_name
            );
        } catch (\Exception) {
            return null;
        }
    }
}
