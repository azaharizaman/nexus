<?php

declare(strict_types=1);

namespace Nexus\Accounting\Contracts;

interface JournalRepositoryInterface
{
    public function createHeader(array $data): JournalEntryInterface;

    public function addLine(int $journalId, array $lineData): void;

    public function post(int $journalId): void;

    public function find(int $journalId): ?JournalEntryInterface;
}
