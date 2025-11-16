<?php

declare(strict_types=1);

namespace App\Repositories\Backoffice;

use App\Models\UnitGroup;
use Nexus\Backoffice\Contracts\UnitGroupInterfaceRepositoryInterface;
use Nexus\Backoffice\Contracts\UnitGroupInterface;

/**
 * UnitGroup Repository
 * 
 * Concrete implementation of UnitGroupInterfaceRepositoryInterface using Eloquent ORM.
 */
class UnitGroupRepository implements UnitGroupInterfaceRepositoryInterface
{
    public function findById(int $id): ?UnitGroupInterface
    {
        return UnitGroup::find($id);
    }

    public function findByCode(string $code): ?UnitGroupInterface
    {
        return UnitGroup::where('code', $code)->first();
    }

    public function getAll(array $filters = []): iterable
    {
        $query = UnitGroup::query();

        foreach ($filters as $key => $value) {
            $query->where($key, $value);
        }

        return $query->get();
    }

    public function getAllActive(): iterable
    {
        return UnitGroup::where('is_active', true)->get();
    }



    public function create(array $data): UnitGroupInterface
    {
        return UnitGroup::create($data);
    }

    public function update(int $id, array $data): UnitGroupInterface
    {
        $model = UnitGroup::findOrFail($id);
        $model->update($data);
        return $model->fresh();
    }

    public function delete(int $id): bool
    {
        $model = UnitGroup::findOrFail($id);
        return $model->delete();
    }

    public function exists(int $id): bool
    {
        return UnitGroup::where('id', $id)->exists();
    }
}