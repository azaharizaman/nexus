<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SerialNumbering\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sequence Overridden Event
 *
 * Dispatched when a serial number is manually overridden.
 */
class SequenceOverriddenEvent
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @param  string  $overrideNumber  The overridden serial number
     * @param  string  $reason  Reason for override
     */
    public function __construct(
        public readonly string $tenantId,
        public readonly string $sequenceName,
        public readonly string $overrideNumber,
        public readonly string $reason
    ) {}
}
