<?php

declare(strict_types=1);

namespace App\Support\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Search Service Contract
 *
 * Defines the interface for search operations, abstracting
 * the underlying implementation (Scout, database, Meilisearch, etc.)
 */
interface SearchServiceContract
{
    /**
     * Search for models matching the query
     *
     * @param  string  $modelClass  Fully qualified model class name
     * @param  string  $query  Search query string
     * @param  array<string, mixed>  $options  Additional search options (filters, pagination, etc.)
     * @return Collection<int, Model>
     */
    public function search(string $modelClass, string $query, array $options = []): Collection;

    /**
     * Perform a raw search and return results
     *
     * @param  string  $index  Index name to search in
     * @param  string  $query  Search query string
     * @param  array<string, mixed>  $options  Search options
     * @return array<string, mixed> Raw search results
     */
    public function searchRaw(string $index, string $query, array $options = []): array;

    /**
     * Add a model to the search index
     *
     * @param  Model  $model  The model to index
     */
    public function index(Model $model): void;

    /**
     * Update a model in the search index
     *
     * @param  Model  $model  The model to update
     */
    public function update(Model $model): void;

    /**
     * Remove a model from the search index
     *
     * @param  Model  $model  The model to remove
     */
    public function removeFromIndex(Model $model): void;

    /**
     * Remove all models of a given class from the search index
     *
     * @param  string  $modelClass  Fully qualified model class name
     */
    public function flush(string $modelClass): void;

    /**
     * Check if a model is searchable
     *
     * @param  Model  $model  The model to check
     * @return bool True if model is searchable
     */
    public function isSearchable(Model $model): bool;
}
