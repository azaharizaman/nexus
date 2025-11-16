<?php

declare(strict_types=1);

namespace Nexus\Analytics\Core\Engine;

use Nexus\Analytics\Core\Contracts\AnalyticsEngineContract;
use Nexus\Analytics\Core\ValueObjects\QueryDefinition;
use Nexus\Analytics\Core\ValueObjects\QueryResult;

class AnalyticsEngine implements AnalyticsEngineContract
{
    public function execute(QueryDefinition $definition): QueryResult
    {
        $def = $definition->definition;

        // For Level 1: definitions are closures applied to the Eloquent query
        if (isset($def['select']) && is_callable($def['select']) && isset($def['model'])) {
            $model = $def['model'];
            $query = $model->newQuery();

            // Guard checks
            if (!empty($def['guards']) && is_array($def['guards'])) {
                foreach ($def['guards'] as $guard) {
                    if (is_callable($guard) && ! $guard($query)) {
                        return new QueryResult([]);
                    }
                }
            }

            // Before hook
            if (!empty($def['before']) && is_callable($def['before'])) {
                $def['before']($query);
            }

            // Execute select closure
            $result = $def['select']($query);

            // After hook
            if (!empty($def['after']) && is_callable($def['after'])) {
                $def['after']($query, $result);
            }

            return new QueryResult($result instanceof \Illuminate\Database\Eloquent\Collection ? $result->toArray() : (array)$result);
        }

        // Placeholder for more advanced definition types (DB-driven, aggregations)
        return new QueryResult([]);
    }
}
