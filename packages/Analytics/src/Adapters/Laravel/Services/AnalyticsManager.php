<?php

declare(strict_types=1);

namespace Nexus\Analytics\Adapters\Laravel\Services;

use Nexus\Analytics\Core\Engine\AnalyticsEngine;
use Nexus\Analytics\Core\ValueObjects\QueryDefinition;
use Nexus\Analytics\Core\ValueObjects\QueryResult;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\DatabaseTransactions;
use Nexus\Atomy\Support\Contracts\ActivityLoggerContract;
use App\Models\AnalyticsHistory;

class AnalyticsManager
{
    public function __construct(private readonly AnalyticsEngine $engine, private readonly ActivityLoggerContract $activityLogger) {}

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
        // set the model for the engine
        $def['model'] = $this->subject;

        $queryDef = new QueryDefinition($name,$def);

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
                            // if any guard denies, deny
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
        return \App\Models\AnalyticsHistory::where('subject_type', get_class($this->subject))
            ->where('subject_id', $this->subject->getKey())
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    private function executeWithTransaction(QueryDefinition $queryDef): QueryResult
    {
        return \DB::transaction(function () use ($queryDef) {
            $result = $this->engine->execute($queryDef);

            // log to history
            AnalyticsHistory::create([
                'subject_type' => get_class($this->subject),
                'subject_id' => $this->subject->getKey(),
                'query_name' => $queryDef->name,
                'result' => json_encode($result->rows),
                'actor_id' => auth()->id(),
                'tenant_id' => method_exists($this->subject, 'getTenantId') ? $this->subject->getTenantId() : ($this->subject->tenant_id ?? null),
            ]);

            // activity logging
            $this->activityLogger->log('Analytics query executed: ' . $queryDef->name, $this->subject, auth()->user(), [
                'rows' => count($result->rows)
            ], 'analytics');

            return $result;
        });
    }
}
