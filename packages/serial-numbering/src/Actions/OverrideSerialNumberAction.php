<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SerialNumbering\Actions;

use Azaharizaman\Erp\SerialNumbering\Contracts\PatternParserContract;
use Azaharizaman\Erp\SerialNumbering\Contracts\SequenceRepositoryContract;
use Azaharizaman\Erp\SerialNumbering\Events\SequenceOverriddenEvent;
use Azaharizaman\Erp\SerialNumbering\Exceptions\DuplicateNumberException;
use Azaharizaman\Erp\SerialNumbering\Exceptions\SequenceNotFoundException;
use Azaharizaman\Erp\SerialNumbering\Models\SerialNumberLog;
use Illuminate\Support\Facades\Gate;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Override Serial Number Action
 *
 * Allows super-admins to manually set a specific serial number
 * for exceptional cases with full audit logging.
 */
class OverrideSerialNumberAction
{
    use AsAction;

    /**
     * Create a new action instance.
     *
     * @param  SequenceRepositoryContract  $repository  The sequence repository
     * @param  PatternParserContract  $parser  The pattern parser
     */
    public function __construct(
        private readonly SequenceRepositoryContract $repository,
        private readonly PatternParserContract $parser
    ) {}

    /**
     * Handle the action.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @param  string  $overrideNumber  The number to set
     * @param  string  $reason  Reason for override
     * @return void
     *
     * @throws SequenceNotFoundException
     * @throws DuplicateNumberException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function handle(
        string $tenantId,
        string $sequenceName,
        string $overrideNumber,
        string $reason
    ): void {
        // Check authorization
        if (auth()->check()) {
            Gate::authorize('override-sequence-number');
        }

        // Find sequence
        $sequence = $this->repository->find($tenantId, $sequenceName);

        if ($sequence === null) {
            throw SequenceNotFoundException::create($tenantId, $sequenceName);
        }

        // Check if number already exists
        $existingLog = SerialNumberLog::where('tenant_id', $tenantId)
            ->where('sequence_name', $sequenceName)
            ->where('generated_number', $overrideNumber)
            ->first();

        if ($existingLog !== null) {
            throw DuplicateNumberException::create($overrideNumber, $sequenceName);
        }

        // Log the override
        $causerType = null;
        $causerId = null;

        if (auth()->check()) {
            $causer = auth()->user();
            $causerType = get_class($causer);
            $causerId = $causer->id;
        }

        SerialNumberLog::create([
            'tenant_id' => $tenantId,
            'sequence_name' => $sequenceName,
            'generated_number' => $overrideNumber,
            'causer_type' => $causerType,
            'causer_id' => $causerId,
            'metadata' => [
                'override' => true,
                'reason' => $reason,
            ],
            'created_at' => now(),
        ]);

        // Dispatch event
        event(new SequenceOverriddenEvent(
            $tenantId,
            $sequenceName,
            $overrideNumber,
            $reason
        ));
    }
}
