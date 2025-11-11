<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Token Resource
 *
 * Transforms authentication token response to JSON:API format.
 */
class TokenResource extends JsonResource
{
    /**
     * Transform the resource into an array
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->resource['token'],
            'token_type' => 'Bearer',
            'expires_at' => $this->resource['expires_at']->toIso8601String(),
            'user' => new UserResource($this->resource['user']),
        ];
    }
}
