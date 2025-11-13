<?php

declare(strict_types=1);

namespace Nexus\Erp\SerialNumbering\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sequence Generated Event
 *
 * Dispatched when a new serial number is generated.
 */
class SequenceGeneratedEvent
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @param  string  $generatedNumber  The generated serial number
     * @param  int  $counterValue  The counter value used
     */
    public function __construct(
        public readonly string $tenantId,
        public readonly string $sequenceName,
        public readonly string $generatedNumber,
        public readonly int $counterValue
    ) {}
}
