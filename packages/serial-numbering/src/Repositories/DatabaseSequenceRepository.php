<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SerialNumbering\Repositories;

use Azaharizaman\Erp\SerialNumbering\Contracts\SequenceRepositoryContract;
use Azaharizaman\Erp\SerialNumbering\Exceptions\SequenceNotFoundException;
use Azaharizaman\Erp\SerialNumbering\Models\Sequence;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Database Sequence Repository
 *
 * Implements sequence data access using Eloquent with row-level locking
 * for atomic counter increment.
 */
class DatabaseSequenceRepository implements SequenceRepositoryContract
{
    /**
     * Find a sequence by tenant ID and sequence name.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @return Sequence|null The sequence or null if not found
     */
    public function find(string $tenantId, string $sequenceName): ?Sequence
    {
        return Sequence::where('tenant_id', $tenantId)
            ->where('sequence_name', $sequenceName)
            ->first();
    }

    /**
     * Lock a sequence row and increment its counter atomically.
     *
     * Uses SELECT FOR UPDATE to prevent race conditions.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @return int The new counter value after increment
     *
     * @throws SequenceNotFoundException
     */
    public function lockAndIncrement(string $tenantId, string $sequenceName): int
    {
        return DB::transaction(function () use ($tenantId, $sequenceName) {
            // Lock the row with SELECT FOR UPDATE
            $sequence = Sequence::where('tenant_id', $tenantId)
                ->where('sequence_name', $sequenceName)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                throw SequenceNotFoundException::create($tenantId, $sequenceName);
            }

            // Increment counter
            $sequence->current_value++;
            $sequence->save();

            return $sequence->current_value;
        });
    }

    /**
     * Create a new sequence configuration.
     *
     * @param  array<string, mixed>  $data  Sequence data
     * @return Sequence The created sequence
     */
    public function create(array $data): Sequence
    {
        return Sequence::create($data);
    }

    /**
     * Update an existing sequence configuration.
     *
     * @param  Sequence  $sequence  The sequence to update
     * @param  array<string, mixed>  $data  Updated data
     * @return bool Success status
     */
    public function update(Sequence $sequence, array $data): bool
    {
        return $sequence->update($data);
    }

    /**
     * Reset a sequence counter to zero.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @return void
     *
     * @throws SequenceNotFoundException
     */
    public function reset(string $tenantId, string $sequenceName): void
    {
        $sequence = $this->find($tenantId, $sequenceName);

        if ($sequence === null) {
            throw SequenceNotFoundException::create($tenantId, $sequenceName);
        }

        $sequence->current_value = 0;
        $sequence->last_reset_at = now();
        $sequence->save();
    }

    /**
     * Delete a sequence configuration.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @return bool Success status
     */
    public function delete(string $tenantId, string $sequenceName): bool
    {
        $sequence = $this->find($tenantId, $sequenceName);

        if ($sequence === null) {
            return false;
        }

        return (bool) $sequence->delete();
    }

    /**
     * Get all sequences for a tenant.
     *
     * @param  string  $tenantId  The tenant identifier
     * @return Collection<int, Sequence>
     */
    public function getAllForTenant(string $tenantId): Collection
    {
        return Sequence::where('tenant_id', $tenantId)
            ->orderBy('sequence_name')
            ->get();
    }
}
