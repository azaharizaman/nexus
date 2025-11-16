<?php

declare(strict_types=1);

namespace App\Repositories\Backoffice;

use App\Models\Department;
use Nexus\Backoffice\Contracts\DepartmentInterfaceRepositoryInterface;
use Nexus\Backoffice\Contracts\DepartmentInterface;

/**
 * Department Repository
 * 
 * Concrete implementation of DepartmentInterfaceRepositoryInterface using Eloquent ORM.
 */
class DepartmentRepository implements DepartmentInterfaceRepositoryInterface
{
    public function findById(int $id): ?DepartmentInterface
    {
        return Department::find($id);
    }

    public function findByCode(string $code): ?DepartmentInterface
    {
        return Department::where('code', $code)->first();
    }

    public function getAll(array $filters = []): iterable
    {
        $query = Department::query();

        foreach ($filters as $key => $value) {
            $query->where($key, $value);
        }

        return $query->get();
    }

    public function getAllActive(): iterable
    {
        return Department::where('is_active', true)->get();
    }


    public function getByOffice(int $office): iterable
    {
        return Department::where('office_id', $office)->get();
    }

    public function getChildren(int $parentId): iterable
    {
        return Department::where('parent_department_id', $parentId)->get();
    }


    public function create(array $data): DepartmentInterface
    {
        return Department::create($data);
    }

    public function update(int $id, array $data): DepartmentInterface
    {
        $model = Department::findOrFail($id);
        $model->update($data);
        return $model->fresh();
    }

    public function delete(int $id): bool
    {
        $model = Department::findOrFail($id);
        return $model->delete();
    }

    public function exists(int $id): bool
    {
        return Department::where('id', $id)->exists();
    }
}