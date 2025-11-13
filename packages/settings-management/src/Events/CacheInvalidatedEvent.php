<?php

declare(strict_types=1);

namespace Nexus\Erp\SettingsManagement\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Cache Invalidated Event
 *
 * Dispatched when setting cache is invalidated.
 */
class CacheInvalidatedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param array<int, string> $cacheKeys The cache keys that were invalidated
     * @param string $reason The reason for invalidation
     */
    public function __construct(
        public readonly array $cacheKeys,
        public readonly string $reason
    ) {}
}
