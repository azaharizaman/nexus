<?php

declare(strict_types=1);

namespace Nexus\Erp\Actions\AuditLog;

use Lorisleiva\Actions\Concerns\AsAction;
use Nexus\AuditLog\Contracts\AuditLogRepositoryContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;

/**
 * Get Audit Log Statistics Action
 *
 * Retrieves statistical information about audit logs.
 */
class GetAuditLogStatisticsAction
{
    use AsAction;

    public function __construct(
        protected AuditLogRepositoryContract $repository
    ) {}

    /**
     * Handle the statistics retrieval
     *
     * @param array $filters Statistics filters
     * @return array
     */
    public function handle(array $filters = []): array
    {
        // Auto-inject tenant_id from authenticated user
        if (auth()->check() && isset(auth()->user()->tenant_id)) {
            $filters['tenant_id'] = auth()->user()->tenant_id;
        }

        return $this->repository->getStatistics($filters);
    }

    /**
     * Handle HTTP request with validation and authorization
     */
    public function asController(Request $request): JsonResponse
    {
        Gate::authorize('view-audit-logs');

        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'log_name' => ['nullable', 'string'],
        ]);

        $statistics = $this->handle($validated);

        return response()->json([
            'data' => $statistics,
        ], 200);
    }
}