<?php

declare(strict_types=1);

namespace Nexus\Contracts\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Base repository contract for all domain repositories
 *
 * @package Nexus\Contracts
 */
interface RepositoryContract
{
    /**
     * Find a model by its primary key
     *
     * @param int|string $id
     * @return Model|null
     */
    public function findById(int|string $id): ?Model;

    /**
     * Find all models
     *
     * @return Collection
     */
    public function findAll(): Collection;

    /**
     * Create a new model
     *
     * @param array<string, mixed> $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Update an existing model
     *
     * @param Model $model
     * @param array<string, mixed> $data
     * @return Model
     */
    public function update(Model $model, array $data): Model;

    /**
     * Delete a model
     *
     * @param Model $model
     * @return bool
     */
    public function delete(Model $model): bool;
}
