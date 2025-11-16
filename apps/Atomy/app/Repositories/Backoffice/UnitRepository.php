<?php

declare(strict_types=1);

namespace App\Repositories\Backoffice;

use App\Models\Unit;
use Nexus\Backoffice\Contracts\UnitInterfaceRepositoryInterface;
use Nexus\Backoffice\Contracts\UnitInterface;

/**
 * Unit Repository
 * 
 * Concrete implementation of UnitInterfaceRepositoryInterface using Eloquent ORM.
 */
class UnitRepository implements UnitInterfaceRepositoryInterface
{
    public function findById(int $id): ?UnitInterface
    {
        return Unit::find($id);
    }

    public function findByCode(string $code): ?UnitInterface
    {
        return Unit::where('code', $code)->first();
    }

    public function getAll(array $filters = []): iterable
    {
        $query = Unit::query();

        foreach ($filters as $key => $value) {
            $query->where($key, $value);
        }

        return $query->get();
    }

    public function getAllActive(): iterable
    {
        return Unit::where('is_active', true)->get();
    }


    public function getByUnitGroup(int $unitGroup): iterable
    {
        return Unit::where('unit_group_id', $unitGroup)->get();
    }

    public function getChildren(int $parentId): iterable
    {
        return Unit::where('parent_unit_id', $parentId)->get();
    }


    public function create(array $data): UnitInterface
    {
        return Unit::create($data);
    }

    public function update(int $id, array $data): UnitInterface
    {
        $model = Unit::findOrFail($id);
        $model->update($data);
        return $model->fresh();
    }

    public function delete(int $id): bool
    {
        $model = Unit::findOrFail($id);
        return $model->delete();
    }

    public function exists(int $id): bool
    {
        return Unit::where('id', $id)->exists();
    }
}