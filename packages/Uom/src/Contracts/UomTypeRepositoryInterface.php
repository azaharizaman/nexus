<?php

declare(strict_types=1);

namespace Nexus\Uom\Contracts;

/**
 * UOM Type Repository Contract
 *
 * Defines persistence operations for UOM types.
 */
interface UomTypeRepositoryInterface
{
    /**
     * Find a type by ID
     *
     * @param int $id
     * @return UomTypeInterface|null
     */
    public function findById(int $id): ?UomTypeInterface;

    /**
     * Find a type by code
     *
     * @param string $code
     * @return UomTypeInterface|null
     */
    public function findByCode(string $code): ?UomTypeInterface;

    /**
     * Get all active types
     *
     * @return array<int, UomTypeInterface>
     */
    public function getAllActive(): array;

    /**
     * Create a new type
     *
     * @param array<string, mixed> $data
     * @return UomTypeInterface
     */
    public function create(array $data): UomTypeInterface;

    /**
     * Update an existing type
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return UomTypeInterface
     */
    public function update(int $id, array $data): UomTypeInterface;

    /**
     * Delete a type
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;
}
