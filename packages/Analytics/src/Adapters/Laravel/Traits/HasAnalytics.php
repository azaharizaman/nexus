<?php

declare(strict_types=1);

namespace Nexus\Analytics\Adapters\Laravel\Traits;

use Nexus\Analytics\Adapters\Laravel\Services\AnalyticsManager;

trait HasAnalytics
{
    public function analytics(): AnalyticsManager
    {
        return app(AnalyticsManager::class)->forSubject($this);
    }
}
