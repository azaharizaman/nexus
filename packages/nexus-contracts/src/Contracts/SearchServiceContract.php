<?php

declare(strict_types=1);

namespace Nexus\Contracts\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Search service contract
 *
 * @package Nexus\Contracts
 */
interface SearchServiceContract
{
    /**
     * Search models by query
     *
     * @param string $modelClass
     * @param string $query
     * @param array<string, mixed> $options
     * @return Collection
     */
    public function search(string $modelClass, string $query, array $options = []): Collection;

    /**
     * Index a model for search
     *
     * @param Model $model
     * @return void
     */
    public function index(Model $model): void;

    /**
     * Remove a model from search index
     *
     * @param Model $model
     * @return void
     */
    public function removeFromIndex(Model $model): void;

    /**
     * Flush search index for a model class
     *
     * @param string $modelClass
     * @return void
     */
    public function flush(string $modelClass): void;
}
