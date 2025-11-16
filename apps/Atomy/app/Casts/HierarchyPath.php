<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Hierarchy Path Cast
 * 
 * Casts hierarchy path as a collection of IDs representing the path
 * from root to the current model.
 */
class HierarchyPath implements CastsAttributes
{
    /**
     * Cast the given value to a collection.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if (is_null($value)) {
            return null;
        }

        if (is_string($value)) {
            return json_decode($value, true);
        }

        return $value;
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        if (is_string($value)) {
            // Validate that it's a valid JSON string
            $decoded = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('Invalid JSON string provided for hierarchy path.');
            }
            return $value;
        }

        throw new InvalidArgumentException('Hierarchy path must be an array or valid JSON string.');
    }
}