<?php

namespace Nexus\Atomy\Repositories\Accounting;

use Nexus\Accounting\Contracts\AccountRepositoryInterface;
use Nexus\Accounting\Contracts\AccountInterface;
use App\Models\Account;

class DbAccountRepository implements AccountRepositoryInterface
{
    public function find(int $id): ?AccountInterface
    {
        return Account::find($id);
    }

    public function findByCode(string $code, int $tenantId): ?AccountInterface
    {
        return Account::where('tenant_id', $tenantId)->where('code', $code)->first();
    }

    public function create(array $data): AccountInterface
    {
        return Account::create($data);
    }

    public function update(int $id, array $data): AccountInterface
    {
        $acc = Account::findOrFail($id);
        $acc->update($data);
        return $acc;
    }

    public function delete(int $id): void
    {
        $hasTransactions = \App\Models\JournalLine::where('account_id', $id)->exists();
        if ($hasTransactions) {
            throw new \Nexus\Accounting\Exceptions\AccountingException('Cannot delete account with associated transactions');
        }

        $hasChildren = Account::where('parent_id', $id)->exists();
        if ($hasChildren) {
            throw new \Nexus\Accounting\Exceptions\AccountingException('Cannot delete account with child accounts');
        }

        Account::destroy($id);
    }

    public function getTree(int $tenantId): array
    {
        // Simple nested set retrieval: order by left column
        $rows = Account::where('tenant_id', $tenantId)->orderBy('lft')->get();
        return $rows->toArray();
    }

    public function addChild(int $parentId, array $data): AccountInterface
    {
        $parent = Account::findOrFail($parentId);
        // NOTE: Not implementing full nested set rebalancing here; simple append to parent's right
        $data['parent_id'] = $parentId;
        // naive: set lft/rgt placeholders
        $data['lft'] = $parent->rgt ?? 0;
        $data['rgt'] = ($parent->rgt ?? 0) + 1;
        $acc = Account::create($data);

        return $acc;
    }

    public function isLeaf(int $accountId): bool
    {
        $acc = Account::findOrFail($accountId);
        if ($acc->lft !== null && $acc->rgt !== null) {
            return ($acc->rgt - $acc->lft) === 1;
        }
        // If nested set is not initialized, use children query
        return Account::where('parent_id', $accountId)->count() === 0;
    }
}
