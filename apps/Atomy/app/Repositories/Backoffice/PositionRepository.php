<?php

declare(strict_types=1);

namespace App\Repositories\Backoffice;

use App\Models\Position;
use Nexus\Backoffice\Contracts\PositionInterfaceRepositoryInterface;
use Nexus\Backoffice\Contracts\PositionInterface;

/**
 * Position Repository
 * 
 * Concrete implementation of PositionInterfaceRepositoryInterface using Eloquent ORM.
 */
class PositionRepository implements PositionInterfaceRepositoryInterface
{
    public function findById(int $id): ?PositionInterface
    {
        return Position::find($id);
    }

    public function findByCode(string $code): ?PositionInterface
    {
        return Position::where('code', $code)->first();
    }

    public function getAll(array $filters = []): iterable
    {
        $query = Position::query();

        foreach ($filters as $key => $value) {
            $query->where($key, $value);
        }

        return $query->get();
    }

    public function getAllActive(): iterable
    {
        return Position::where('is_active', true)->get();
    }


    public function getByType(string $type): iterable
    {
        return Position::where('type', $type)->get();
    }

    public function getByLevel(string $level): iterable
    {
        return Position::where('level', $level)->get();
    }


    public function create(array $data): PositionInterface
    {
        return Position::create($data);
    }

    public function update(int $id, array $data): PositionInterface
    {
        $model = Position::findOrFail($id);
        $model->update($data);
        return $model->fresh();
    }

    public function delete(int $id): bool
    {
        $model = Position::findOrFail($id);
        return $model->delete();
    }

    public function exists(int $id): bool
    {
        return Position::where('id', $id)->exists();
    }
}