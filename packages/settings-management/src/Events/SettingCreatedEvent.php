<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SettingsManagement\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Setting Created Event
 *
 * Dispatched when a new setting is created.
 */
class SettingCreatedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param int $settingId The setting ID
     * @param string $key The setting key
     * @param mixed $value The setting value
     * @param string $type The value type
     * @param string $scope The scope level
     * @param int|null $tenantId The tenant ID
     */
    public function __construct(
        public readonly int $settingId,
        public readonly string $key,
        public readonly mixed $value,
        public readonly string $type,
        public readonly string $scope,
        public readonly ?int $tenantId = null
    ) {}
}
