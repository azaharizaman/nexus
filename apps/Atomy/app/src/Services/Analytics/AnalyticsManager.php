<?php

declare(strict_types=1);

namespace Nexus\Atomy\Services\Analytics;

use Nexus\Analytics\Core\Contracts\AnalyticsEngineContract as AnalyticsEngineContract;
use Nexus\Analytics\Core\ValueObjects\QueryDefinition;
use Nexus\Analytics\Core\ValueObjects\QueryResult;
use Nexus\Atomy\Support\Contracts\ActivityLoggerContract;
use App\Models\AnalyticsHistory;

class AnalyticsManager
{
    public function __construct(private readonly AnalyticsEngineContract $engine, private readonly ActivityLoggerContract $activityLogger) {}

    private $subject;

    public function forSubject($subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function runQuery(string $name): QueryResult
    {
        $definitions = $this->subject->analytics();

        if (!isset($definitions['queries'][$name])) {
            return new QueryResult([]);
        }

        $def = $definitions['queries'][$name];
        $def['model'] = $this->subject;

        $queryDef = new QueryDefinition($name, $def);

        return $this->executeWithTransaction($queryDef);
    }

    public function can(string $action): bool
    {
        $definitions = $this->subject->analytics();
        if (!empty($definitions['queries'])) {
            foreach ($definitions['queries'] as $q) {
                if (!empty($q['guards']) && is_array($q['guards'])) {
                    foreach ($q['guards'] as $guard) {
                        if (is_callable($guard)) {
                            if (! $guard($this->subject->newQuery())) {
                                return false;
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    public function history(): array
    {
        return AnalyticsHistory::where('subject_type', get_class($this->subject))
            ->where('subject_id', $this->subject->getKey())
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    private function executeWithTransaction(QueryDefinition $queryDef): QueryResult
    {
        return \DB::transaction(function () use ($queryDef) {
            $result = $this->engine->execute($queryDef);

            AnalyticsHistory::create([
                'subject_type' => get_class($this->subject),
                'subject_id' => $this->subject->getKey(),
                'query_name' => $queryDef->name,
                'result' => json_encode($result->rows),
                'actor_id' => auth()->id(),
                'tenant_id' => method_exists($this->subject, 'getTenantId') ? $this->subject->getTenantId() : ($this->subject->tenant_id ?? null),
            ]);

            $this->activityLogger->log('Analytics query executed: ' . $queryDef->name, $this->subject, auth()->user(), [
                'rows' => count($result->rows)
            ], 'analytics');

            return $result;
        });
    }
}
