<?php

declare(strict_types=1);

namespace App\Repositories\Backoffice;

use App\Models\Staff;
use Nexus\Backoffice\Contracts\StaffInterfaceRepositoryInterface;
use Nexus\Backoffice\Contracts\StaffInterface;

/**
 * Staff Repository
 * 
 * Concrete implementation of StaffInterfaceRepositoryInterface using Eloquent ORM.
 */
class StaffRepository implements StaffInterfaceRepositoryInterface
{
    public function findById(int $id): ?StaffInterface
    {
        return Staff::find($id);
    }

    public function findByCode(string $code): ?StaffInterface
    {
        return Staff::where('code', $code)->first();
    }

    public function getAll(array $filters = []): iterable
    {
        $query = Staff::query();

        foreach ($filters as $key => $value) {
            $query->where($key, $value);
        }

        return $query->get();
    }

    public function getAllActive(): iterable
    {
        return Staff::where('is_active', true)->get();
    }


    public function findByEmployeeNumber(string $employeeNumber): ?StaffInterface
    {
        return Staff::where('employee_number', $employeeNumber)->first();
    }

    public function findByUserId(int $userId): ?StaffInterface
    {
        return Staff::where('user_id', $userId)->first();
    }

    public function getByDepartment(int $department): iterable
    {
        return Staff::where('department_id', $department)->get();
    }

    public function getByPosition(int $position): iterable
    {
        return Staff::where('position_id', $position)->get();
    }

    public function getDirectReports(int $directReports): iterable
    {
        return Staff::where('reports_to_id', $directReports)->get();
    }

    public function getByStatus(string $status): iterable
    {
        return Staff::where('status', $status)->get();
    }


    public function create(array $data): StaffInterface
    {
        return Staff::create($data);
    }

    public function update(int $id, array $data): StaffInterface
    {
        $model = Staff::findOrFail($id);
        $model->update($data);
        return $model->fresh();
    }

    public function delete(int $id): bool
    {
        $model = Staff::findOrFail($id);
        return $model->delete();
    }

    public function exists(int $id): bool
    {
        return Staff::where('id', $id)->exists();
    }
}