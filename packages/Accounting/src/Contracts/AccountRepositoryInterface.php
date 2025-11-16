<?php

declare(strict_types=1);

namespace Nexus\Accounting\Contracts;

interface AccountRepositoryInterface
{
    public function find(int $id): ?AccountInterface;

    public function findByCode(string $code, int $tenantId): ?AccountInterface;

    public function create(array $data): AccountInterface;

    public function update(int $id, array $data): AccountInterface;

    public function delete(int $id): void;

    public function getTree(int $tenantId): array;

    public function addChild(int $parentId, array $data): AccountInterface;

    public function isLeaf(int $accountId): bool;
}
