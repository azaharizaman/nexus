<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SerialNumbering\Actions;

use Azaharizaman\Erp\SerialNumbering\Contracts\SequenceRepositoryContract;
use Azaharizaman\Erp\SerialNumbering\Events\SequenceResetEvent;
use Azaharizaman\Erp\SerialNumbering\Exceptions\SequenceNotFoundException;
use Illuminate\Support\Facades\Gate;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Reset Sequence Action
 *
 * Resets a sequence counter to zero with admin authorization
 * and audit logging.
 */
class ResetSequenceAction
{
    use AsAction;

    /**
     * Create a new action instance.
     *
     * @param  SequenceRepositoryContract  $repository  The sequence repository
     */
    public function __construct(
        private readonly SequenceRepositoryContract $repository
    ) {}

    /**
     * Handle the action.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @param  string  $reason  Reason for reset
     * @return void
     *
     * @throws SequenceNotFoundException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function handle(string $tenantId, string $sequenceName, string $reason): void
    {
        // Check authorization
        if (auth()->check()) {
            Gate::authorize('reset-sequence');
        }

        // Find sequence before reset
        $sequence = $this->repository->find($tenantId, $sequenceName);

        if ($sequence === null) {
            throw SequenceNotFoundException::create($tenantId, $sequenceName);
        }

        $previousValue = $sequence->current_value;

        // Reset the sequence
        $this->repository->reset($tenantId, $sequenceName);

        // Dispatch event
        event(new SequenceResetEvent(
            $tenantId,
            $sequenceName,
            $reason,
            $previousValue,
            now()
        ));
    }
}
