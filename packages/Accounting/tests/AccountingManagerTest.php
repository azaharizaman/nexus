<?php

declare(strict_types=1);

use Nexus\Accounting\Services\AccountingManager;
use Nexus\Accounting\Contracts\AccountRepositoryInterface;
use Nexus\Accounting\Contracts\JournalRepositoryInterface;
use Nexus\Accounting\Contracts\AccountInterface as AccountInterfaceContract;
use Nexus\Accounting\Contracts\JournalEntryInterface as JournalEntryInterfaceContract;
use Nexus\Atomy\Support\Contracts\ActivityLoggerContract;

use PHPUnit\Framework\TestCase;

class DummyAccount implements AccountInterfaceContract
{
    public function getId(): int { return 1; }
    public function getTenantId(): int { return 1; }
    public function getName(): string { return 'Test'; }
    public function getCode(): string { return '1000'; }
    public function getType(): string { return 'Asset'; }
    public function isActive(): bool { return true; }
    public function getParentId(): ?int { return null; }
    public function getLeft(): int { return 1; }
    public function getRight(): int { return 2; }
    public function getTags(): array { return []; }
}

class DummyAccountRepo implements AccountRepositoryInterface
{
    public function find(int $id): ?AccountInterfaceContract { return new DummyAccount(); }
    public function findByCode(string $code, int $tenantId): ?AccountInterfaceContract { return new DummyAccount(); }
    public function create(array $data): AccountInterfaceContract { return new DummyAccount(); }
    public function update(int $id, array $data): AccountInterfaceContract { return new DummyAccount(); }
    public function delete(int $id): void {}
    public function getTree(int $tenantId): array { return []; }
    public function addChild(int $parentId, array $data): AccountInterfaceContract { return new DummyAccount(); }
}

class DummyJournal implements JournalEntryInterfaceContract
{
    public function getId(): int { return 1; }
    public function getTenantId(): int { return 1; }
    public function getPostedAt(): ?\DateTimeImmutable { return null; }
    public function getDescription(): string { return 'Dummy'; }
    public function isPosted(): bool { return false; }
}

class DummyJournalRepo implements JournalRepositoryInterface
{
    public function createHeader(array $data): JournalEntryInterfaceContract { return new DummyJournal(); }
    public function addLine(int $journalId, array $lineData): void {}
    public function post(int $journalId): void {}
    public function find(int $journalId): ?JournalEntryInterfaceContract { return new DummyJournal(); }
}

class DummyActivityLogger implements ActivityLoggerContract {
    public function log(string $description, \Illuminate\Database\Eloquent\Model $subject, ?\Illuminate\Database\Eloquent\Model $causer = null, array $properties = [], ?string $logName = null): void {}
    public function getActivities(\Illuminate\Database\Eloquent\Model $subject) : \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
    public function getByDateRange(Carbon\Carbon $from, Carbon\Carbon $to, ?string $logName = null) : \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
    public function getByCauser(\Illuminate\Database\Eloquent\Model $causer, int $limit = 50): \Illuminate\Database\Eloquent\Collection { return new \Illuminate\Database\Eloquent\Collection(); }
    public function getStatistics(array $filters = []) : array { return []; }
    public function cleanup(Carbon\Carbon $before) : int { return 0; }
}

final class AccountingManagerTest extends TestCase
{
    public function test_post_journal_must_be_balanced()
    {
        $manager = new AccountingManager(new DummyAccountRepo(), new DummyJournalRepo(), new DummyActivityLogger());

        $this->expectException(\Nexus\Accounting\Exceptions\AccountingException::class);

        $header = ['tenant_id' => 1, 'description' => 'Test'];
        $lines = [
            ['account_id' => 10, 'debit' => 100, 'credit' => 0],
            ['account_id' => 11, 'debit' => 0, 'credit' => 50],
        ];

        $manager->postJournal($header,$lines);
    }
}
