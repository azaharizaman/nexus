<?php

declare(strict_types=1);

namespace App\Repositories\Backoffice;

use App\Models\Office;
use Nexus\Backoffice\Contracts\OfficeInterfaceRepositoryInterface;
use Nexus\Backoffice\Contracts\OfficeInterface;

/**
 * Office Repository
 * 
 * Concrete implementation of OfficeInterfaceRepositoryInterface using Eloquent ORM.
 */
class OfficeRepository implements OfficeInterfaceRepositoryInterface
{
    public function findById(int $id): ?OfficeInterface
    {
        return Office::find($id);
    }

    public function findByCode(string $code): ?OfficeInterface
    {
        return Office::where('code', $code)->first();
    }

    public function getAll(array $filters = []): iterable
    {
        $query = Office::query();

        foreach ($filters as $key => $value) {
            $query->where($key, $value);
        }

        return $query->get();
    }

    public function getAllActive(): iterable
    {
        return Office::where('is_active', true)->get();
    }


    public function getByCompany(int $company): iterable
    {
        return Office::where('company_id', $company)->get();
    }

    public function getChildren(int $parentId): iterable
    {
        return Office::where('parent_office_id', $parentId)->get();
    }


    public function create(array $data): OfficeInterface
    {
        return Office::create($data);
    }

    public function update(int $id, array $data): OfficeInterface
    {
        $model = Office::findOrFail($id);
        $model->update($data);
        return $model->fresh();
    }

    public function delete(int $id): bool
    {
        $model = Office::findOrFail($id);
        return $model->delete();
    }

    public function exists(int $id): bool
    {
        return Office::where('id', $id)->exists();
    }
}