<?php

namespace Nexus\Atomy\Repositories\Accounting;

use Nexus\Accounting\Contracts\JournalRepositoryInterface;
use Nexus\Accounting\Contracts\JournalEntryInterface;
use App\Models\JournalEntry;
use App\Models\JournalLine;

class DbJournalRepository implements JournalRepositoryInterface
{
    public function createHeader(array $data): JournalEntryInterface
    {
        return JournalEntry::create($data);
    }

    public function addLine(int $journalId, array $lineData): void
    {
        $line = new JournalLine(array_merge($lineData, ['journal_id' => $journalId]));
        $line->save();
    }

    public function post(int $journalId): void
    {
        $journal = JournalEntry::findOrFail($journalId);
        // enforce immutability by setting is_posted and posted_at
        if ($journal->is_posted) {
            return;
        }

        $journal->is_posted = true;
        $journal->posted_at = now();
        $journal->save();
    }

    public function find(int $journalId): ?JournalEntryInterface
    {
        return JournalEntry::find($journalId);
    }
}
