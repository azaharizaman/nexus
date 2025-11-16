<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

/**
 * Position Repository Interface
 * 
 * Defines the persistence contract for Position entities.
 * All database operations for positions must go through this interface.
 */
interface PositionRepositoryInterface
{
    /**
     * Find a position by its ID.
     *
     * @param int $id The position ID
     * @return PositionInterface|null The position or null if not found
     */
    public function findById(int $id): ?PositionInterface;

    /**
     * Find a position by its code.
     *
     * @param string $code The position code
     * @return PositionInterface|null The position or null if not found
     */
    public function findByCode(string $code): ?PositionInterface;

    /**
     * Get all positions.
     *
     * @param array $filters Optional filters (e.g., ['is_active' => true, 'type' => 'permanent'])
     * @return iterable<PositionInterface> Collection of positions
     */
    public function getAll(array $filters = []): iterable;

    /**
     * Get all active positions.
     *
     * @return iterable<PositionInterface> Collection of active positions
     */
    public function getAllActive(): iterable;

    /**
     * Get positions by type.
     *
     * @param string $type The position type
     * @return iterable<PositionInterface> Collection of positions
     */
    public function getByType(string $type): iterable;

    /**
     * Get positions by level.
     *
     * @param int $level The position level
     * @return iterable<PositionInterface> Collection of positions
     */
    public function getByLevel(int $level): iterable;

    /**
     * Create a new position.
     *
     * @param array $data Position data
     * @return PositionInterface The created position
     */
    public function create(array $data): PositionInterface;

    /**
     * Update an existing position.
     *
     * @param int $id The position ID
     * @param array $data Updated position data
     * @return PositionInterface The updated position
     */
    public function update(int $id, array $data): PositionInterface;

    /**
     * Delete a position.
     *
     * @param int $id The position ID
     * @return bool True if deleted successfully
     */
    public function delete(int $id): bool;

    /**
     * Check if a position exists.
     *
     * @param int $id The position ID
     * @return bool True if exists
     */
    public function exists(int $id): bool;
}
