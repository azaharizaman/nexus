<?php

declare(strict_types=1);

namespace Nexus\Erp\Support\Traits;

use Laravel\Scout\Searchable;

/**
 * Trait IsSearchable
 *
 * Wrapper trait for search functionality that decouples business logic
 * from the underlying Laravel Scout package. This trait still uses Scout
 * internally but provides a consistent interface that can be replaced if needed.
 *
 * For direct search operations in services, inject SearchServiceContract instead.
 *
 * Usage:
 * ```
 * class YourModel extends Model
 * {
 *     use IsSearchable;
 *
 *     protected function configureSearchable(): array
 *     {
 *         return [
 *             'index_name' => 'custom_index',
 *             'searchable_fields' => ['name', 'description'],
 *         ];
 *     }
 * }
 * ```
 */
trait IsSearchable
{
    use Searchable;

    /**
     * Get the indexable data array for the model
     *
     * Override configureSearchable() in your model to customize search behavior.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $config = $this->configureSearchable();

        // If specific fields are configured, return only those
        if (isset($config['searchable_fields'])) {
            $array = [];
            foreach ($config['searchable_fields'] as $field) {
                $array[$field] = $this->getAttribute($field);
            }

            // Always include tenant_id for multi-tenancy if it exists
            if (array_key_exists('tenant_id', $this->attributes)) {
                $array['tenant_id'] = $this->tenant_id;
            }

            return $array;
        }

        // Default: convert entire model to array
        $array = $this->toArray();

        // Ensure tenant_id is included for multi-tenancy
        if (array_key_exists('tenant_id', $this->attributes) && ! isset($array['tenant_id'])) {
            $array['tenant_id'] = $this->tenant_id;
        }

        return $array;
    }

    /**
     * Get the name of the index associated with the model
     */
    public function searchableAs(): string
    {
        $config = $this->configureSearchable();

        if (isset($config['index_name'])) {
            return $config['index_name'];
        }

        // Default: use table name
        return $this->getTable();
    }

    /**
     * Configure search behavior for this model
     *
     * Override this method in your model to specify search configuration.
     *
     * Available options:
     * - index_name: Custom search index name (default: table name)
     * - searchable_fields: Array of fields to include in search index (default: all fields)
     *
     * @return array<string, mixed>
     */
    protected function configureSearchable(): array
    {
        return [];
    }
}
