<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Full Name Cast
 * 
 * Concatenates first_name and last_name into a full_name attribute.
 */
class FullName implements CastsAttributes
{
    /**
     * Cast the given value to a full name string.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        $firstName = $attributes['first_name'] ?? '';
        $lastName = $attributes['last_name'] ?? '';
        
        return trim("{$firstName} {$lastName}") ?: null;
    }

    /**
     * Prepare the given value for storage.
     * 
     * Note: This cast is read-only as full_name is computed from first_name and last_name.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        // This is a computed field, so we don't store it directly
        return $value;
    }
}