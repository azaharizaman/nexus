<?php

namespace Nexus\Accounting\Services;

use Nexus\Accounting\Contracts\AccountRepositoryInterface;
use Nexus\Accounting\Contracts\JournalRepositoryInterface;
use Nexus\Atomy\Support\Contracts\ActivityLoggerContract;
use Nexus\Accounting\Exceptions\AccountingException;

class AccountingManager
{
    public function __construct(
        private readonly AccountRepositoryInterface $accounts,
        private readonly JournalRepositoryInterface $journals,
        private readonly ActivityLoggerContract $activityLogger
    ) {
    }

    public function createAccount(array $data)
    {
        // Validate unique code per tenant
        if ($this->accounts->findByCode($data['code'], $data['tenant_id'])) {
            throw new AccountingException('Account code must be unique within tenant');
        }

        // Create account via repository
        return $this->accounts->create($data);
    }

    public function postJournal(array $header, array $lines, ?\Illuminate\Contracts\Auth\Authenticatable $user = null)
    {
        // Create header
        $journal = $this->journals->createHeader($header);

        $debitTotal = 0;
        $creditTotal = 0;

        foreach ($lines as $line) {
            $this->journals->addLine($journal->getId(), $line);
            $debitTotal += $line['debit'] ?? 0;
            $creditTotal += $line['credit'] ?? 0;
            // Business rule: only leaf accounts may have transactions posted
            if (! $this->accounts->isLeaf($line['account_id'])) {
                throw new AccountingException('Entries can only be posted to leaf accounts (no children)');
            }
        }

        if (abs($debitTotal - $creditTotal) > 0.00001) {
            throw new AccountingException('Journal entries must be balanced');
        }

        // Post the journal and add audit
        $this->journals->post($journal->getId());
        /** @var \Illuminate\Database\Eloquent\Model $journalModel */
        $journalModel = $journal;

        $this->activityLogger->log('Journal posted', $journalModel, $user, [
            'journal_id' => $journal->getId(),
            'tenant_id' => $journal->getTenantId(),
        ], 'accounting');

        return $journal;
    }
}