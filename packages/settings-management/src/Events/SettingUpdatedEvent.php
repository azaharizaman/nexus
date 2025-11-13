<?php

declare(strict_types=1);

namespace Nexus\Erp\SettingsManagement\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Setting Updated Event
 *
 * Dispatched when a setting value is updated.
 */
class SettingUpdatedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param int $settingId The setting ID
     * @param string $key The setting key
     * @param mixed $oldValue The previous value
     * @param mixed $newValue The new value
     * @param string $scope The scope level
     * @param int|null $tenantId The tenant ID
     */
    public function __construct(
        public readonly int $settingId,
        public readonly string $key,
        public readonly mixed $oldValue,
        public readonly mixed $newValue,
        public readonly string $scope,
        public readonly ?int $tenantId = null
    ) {}
}
