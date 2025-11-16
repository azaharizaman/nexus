<?php

declare(strict_types=1);

namespace App\Repositories\Backoffice;

use App\Models\Company;
use Nexus\Backoffice\Contracts\CompanyRepositoryInterface;
use Nexus\Backoffice\Contracts\CompanyInterface;

/**
 * Company Repository
 * 
 * Concrete implementation of CompanyRepositoryInterface using Eloquent ORM.
 */
class CompanyRepository implements CompanyRepositoryInterface
{
    public function findById(int $id): ?CompanyInterface
    {
        return Company::find($id);
    }

    public function findByCode(string $code): ?CompanyInterface
    {
        return Company::where('code', $code)->first();
    }

    public function getAll(array $filters = []): iterable
    {
        $query = Company::query();

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->get();
    }

    public function getAllActive(): iterable
    {
        return Company::where('is_active', true)->get();
    }

    public function getChildren(int $parentId): iterable
    {
        return Company::where('parent_company_id', $parentId)->get();
    }

    public function create(array $data): CompanyInterface
    {
        return Company::create($data);
    }

    public function update(int $id, array $data): CompanyInterface
    {
        $company = Company::findOrFail($id);
        $company->update($data);
        return $company->fresh();
    }

    public function delete(int $id): bool
    {
        $company = Company::findOrFail($id);
        return $company->delete();
    }

    public function exists(int $id): bool
    {
        return Company::where('id', $id)->exists();
    }
}
