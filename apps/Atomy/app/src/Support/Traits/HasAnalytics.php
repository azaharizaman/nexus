<?php

declare(strict_types=1);

namespace Nexus\Atomy\Support\Traits;

use Nexus\Atomy\Services\Analytics\AnalyticsManager;

trait HasAnalytics
{
    public function analytics(): AnalyticsManager
    {
        return app(AnalyticsManager::class)->forSubject($this);
    }
}
