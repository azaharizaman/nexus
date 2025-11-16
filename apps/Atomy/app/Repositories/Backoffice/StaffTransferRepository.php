<?php

declare(strict_types=1);

namespace App\Repositories\Backoffice;

use App\Models\StaffTransfer;
use Nexus\Backoffice\Contracts\StaffTransferInterfaceRepositoryInterface;
use Nexus\Backoffice\Contracts\StaffTransferInterface;

/**
 * StaffTransfer Repository
 * 
 * Concrete implementation of StaffTransferInterfaceRepositoryInterface using Eloquent ORM.
 */
class StaffTransferRepository implements StaffTransferInterfaceRepositoryInterface
{
    public function findById(int $id): ?StaffTransferInterface
    {
        return StaffTransfer::find($id);
    }

    public function findByCode(string $code): ?StaffTransferInterface
    {
        return StaffTransfer::where('code', $code)->first();
    }

    public function getAll(array $filters = []): iterable
    {
        $query = StaffTransfer::query();

        foreach ($filters as $key => $value) {
            $query->where($key, $value);
        }

        return $query->get();
    }

    public function getAllActive(): iterable
    {
        return StaffTransfer::where('is_active', true)->get();
    }


    public function getByStaff(int $staff): iterable
    {
        return StaffTransfer::where('staff_id', $staff)->get();
    }

    public function getByStatus(string $status): iterable
    {
        return StaffTransfer::where('status', $status)->get();
    }

    public function getPending(): iterable
    {
        return StaffTransfer::where('status', 'pending')->get();
    }

    public function getApproved(): iterable
    {
        return StaffTransfer::where('status', 'approved')->get();
    }

    public function getEffectiveBy(\DateTimeInterface $date): iterable
    {
        return StaffTransfer::where('effective_date', '<=', $date)->get();
    }


    public function create(array $data): StaffTransferInterface
    {
        return StaffTransfer::create($data);
    }

    public function update(int $id, array $data): StaffTransferInterface
    {
        $model = StaffTransfer::findOrFail($id);
        $model->update($data);
        return $model->fresh();
    }

    public function delete(int $id): bool
    {
        $model = StaffTransfer::findOrFail($id);
        return $model->delete();
    }

    public function exists(int $id): bool
    {
        return StaffTransfer::where('id', $id)->exists();
    }
}