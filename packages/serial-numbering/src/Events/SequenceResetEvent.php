<?php

declare(strict_types=1);

namespace Nexus\Erp\SerialNumbering\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sequence Reset Event
 *
 * Dispatched when a sequence counter is reset.
 */
class SequenceResetEvent
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @param  string  $reason  Reason for reset
     * @param  int  $previousValue  The counter value before reset
     * @param  Carbon  $resetAt  When the reset occurred
     */
    public function __construct(
        public readonly string $tenantId,
        public readonly string $sequenceName,
        public readonly string $reason,
        public readonly int $previousValue,
        public readonly Carbon $resetAt
    ) {}
}
