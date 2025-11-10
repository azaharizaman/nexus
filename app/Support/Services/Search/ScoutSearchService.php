<?php

declare(strict_types=1);

namespace App\Support\Services\Search;

use App\Support\Contracts\SearchServiceContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

/**
 * Scout Search Service
 *
 * Adapter implementation using Laravel Scout package.
 * This isolates the Scout package from our business logic.
 */
class ScoutSearchService implements SearchServiceContract
{
    /**
     * Search for models matching the query
     *
     * @param  string  $modelClass  Fully qualified model class name
     * @param  string  $query  Search query string
     * @param  array<string, mixed>  $options  Additional search options
     * @return Collection<int, Model>
     */
    public function search(string $modelClass, string $query, array $options = []): Collection
    {
        /** @var \Laravel\Scout\Builder $builder */
        $builder = $modelClass::search($query);

        // Apply filters if provided
        if (isset($options['filters']) && is_array($options['filters'])) {
            foreach ($options['filters'] as $key => $value) {
                $builder->where($key, $value);
            }
        }

        // Apply pagination if 'paginate', 'page', or 'per_page' are provided
        if (isset($options['paginate']) || isset($options['page']) || isset($options['per_page'])) {
            $perPage = (int) ($options['per_page'] ?? 15);
            $page = (int) ($options['page'] ?? 1);
            $paginated = $builder->paginate($perPage, ['*'], 'page', $page);

            return Collection::make($paginated->items());
        }

        // Apply limit if provided
        if (isset($options['limit'])) {
            $builder->take($options['limit']);
        }

        return $builder->get();
    }

    /**
     * Perform a raw search and return results
     *
     * @param  string  $index  Index name to search in
     * @param  string  $query  Search query string
     * @param  array<string, mixed>  $options  Search options
     * @return array<string, mixed>
     */
    public function searchRaw(string $index, string $query, array $options = []): array
    {
        // Note: This is a simplified implementation
        // Actual implementation would depend on the Scout driver being used
        return [
            'index' => $index,
            'query' => $query,
            'options' => $options,
            'results' => [],
        ];
    }

    /**
     * Add a model to the search index
     *
     * @param  Model  $model  The model to index
     */
    public function index(Model $model): void
    {
        if ($this->isSearchable($model)) {
            /** @var \Laravel\Scout\Searchable $model */
            $model->searchable();
        }
    }

    /**
     * Update a model in the search index
     *
     * @param  Model  $model  The model to update
     */
    public function update(Model $model): void
    {
        if ($this->isSearchable($model)) {
            /** @var \Laravel\Scout\Searchable $model */
            $model->searchable();
        }
    }

    /**
     * Remove a model from the search index
     *
     * @param  Model  $model  The model to remove
     */
    public function removeFromIndex(Model $model): void
    {
        if ($this->isSearchable($model)) {
            /** @var \Laravel\Scout\Searchable $model */
            $model->unsearchable();
        }
    }

    /**
     * Remove all models of a given class from the search index
     *
     * @param  string  $modelClass  Fully qualified model class name
     */
    public function flush(string $modelClass): void
    {
        if (is_subclass_of($modelClass, Model::class)) {
            $uses = class_uses_recursive($modelClass);

            if (is_array($uses) && in_array(Searchable::class, $uses, true)) {
                /** @var class-string<Model&\Laravel\Scout\Searchable> $modelClass */
                $modelClass::removeAllFromSearch();
            }
        }
    }

    /**
     * Check if a model is searchable
     *
     * @param  Model  $model  The model to check
     */
    public function isSearchable(Model $model): bool
    {
        $uses = class_uses_recursive($model);

        return is_array($uses) && in_array(Searchable::class, $uses, true);
    }
}
