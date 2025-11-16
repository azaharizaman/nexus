<?php

declare(strict_types=1);

namespace Nexus\Analytics\Core\Contracts;

use Nexus\Analytics\Core\ValueObjects\QueryDefinition;
use Nexus\Analytics\Core\ValueObjects\QueryResult;

interface AnalyticsEngineContract
{
    public function execute(QueryDefinition $definition): QueryResult;
}
