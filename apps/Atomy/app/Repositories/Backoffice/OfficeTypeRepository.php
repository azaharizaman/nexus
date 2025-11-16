<?php

declare(strict_types=1);

namespace App\Repositories\Backoffice;

use App\Models\OfficeType;
use Nexus\Backoffice\Contracts\OfficeTypeInterfaceRepositoryInterface;
use Nexus\Backoffice\Contracts\OfficeTypeInterface;

/**
 * OfficeType Repository
 * 
 * Concrete implementation of OfficeTypeInterfaceRepositoryInterface using Eloquent ORM.
 */
class OfficeTypeRepository implements OfficeTypeInterfaceRepositoryInterface
{
    public function findById(int $id): ?OfficeTypeInterface
    {
        return OfficeType::find($id);
    }

    public function findByCode(string $code): ?OfficeTypeInterface
    {
        return OfficeType::where('code', $code)->first();
    }

    public function getAll(array $filters = []): iterable
    {
        $query = OfficeType::query();

        foreach ($filters as $key => $value) {
            $query->where($key, $value);
        }

        return $query->get();
    }

    public function getAllActive(): iterable
    {
        return OfficeType::where('is_active', true)->get();
    }


    public function getByStatus(string $status): iterable
    {
        return OfficeType::where('status', $status)->get();
    }


    public function create(array $data): OfficeTypeInterface
    {
        return OfficeType::create($data);
    }

    public function update(int $id, array $data): OfficeTypeInterface
    {
        $model = OfficeType::findOrFail($id);
        $model->update($data);
        return $model->fresh();
    }

    public function delete(int $id): bool
    {
        $model = OfficeType::findOrFail($id);
        return $model->delete();
    }

    public function exists(int $id): bool
    {
        return OfficeType::where('id', $id)->exists();
    }
}