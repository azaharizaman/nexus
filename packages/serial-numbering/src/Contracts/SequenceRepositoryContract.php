<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SerialNumbering\Contracts;

use Azaharizaman\Erp\SerialNumbering\Models\Sequence;

/**
 * Sequence Repository Contract
 *
 * Defines the interface for sequence data access operations.
 */
interface SequenceRepositoryContract
{
    /**
     * Find a sequence by tenant ID and sequence name.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @return Sequence|null The sequence or null if not found
     */
    public function find(string $tenantId, string $sequenceName): ?Sequence;

    /**
     * Lock a sequence row and increment its counter atomically.
     *
     * This method uses SELECT FOR UPDATE to prevent race conditions
     * and ensures atomic counter increment.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @return int The new counter value after increment
     *
     * @throws \Azaharizaman\Erp\SerialNumbering\Exceptions\SequenceNotFoundException
     */
    public function lockAndIncrement(string $tenantId, string $sequenceName): int;

    /**
     * Create a new sequence configuration.
     *
     * @param  array<string, mixed>  $data  Sequence data
     * @return Sequence The created sequence
     */
    public function create(array $data): Sequence;

    /**
     * Update an existing sequence configuration.
     *
     * @param  Sequence  $sequence  The sequence to update
     * @param  array<string, mixed>  $data  Updated data
     * @return bool Success status
     */
    public function update(Sequence $sequence, array $data): bool;

    /**
     * Reset a sequence counter to zero.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @return void
     *
     * @throws \Azaharizaman\Erp\SerialNumbering\Exceptions\SequenceNotFoundException
     */
    public function reset(string $tenantId, string $sequenceName): void;

    /**
     * Delete a sequence configuration.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @return bool Success status
     */
    public function delete(string $tenantId, string $sequenceName): bool;

    /**
     * Get all sequences for a tenant.
     *
     * @param  string  $tenantId  The tenant identifier
     * @return \Illuminate\Support\Collection<int, Sequence>
     */
    public function getAllForTenant(string $tenantId): \Illuminate\Support\Collection;
}
