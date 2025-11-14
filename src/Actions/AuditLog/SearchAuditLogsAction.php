<?php

declare(strict_types=1);

namespace Nexus\Erp\Actions\AuditLog;

use Lorisleiva\Actions\Concerns\AsAction;
use Nexus\AuditLog\Contracts\AuditLogRepositoryContract;
use Nexus\Erp\Support\Services\Logging\SpatieActivityLoggerAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\Models\Activity;

/**
 * Search Audit Logs Action
 *
 * Handles audit log searching with filtering and pagination.
 * Available as API endpoint and direct invocation.
 */
class SearchAuditLogsAction
{
    use AsAction;

    public function __construct(
        protected AuditLogRepositoryContract $repository,
        protected SpatieActivityLoggerAdapter $adapter
    ) {}

    /**
     * Handle the audit log search
     *
     * @param array $filters Search filters
     * @param int $perPage Results per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function handle(array $filters, int $perPage = 50): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        // Auto-inject tenant_id from authenticated user
        if (auth()->check() && isset(auth()->user()->tenant_id)) {
            $filters['tenant_id'] = auth()->user()->tenant_id;
        }

        return $this->repository->search($filters, $perPage);
    }

    /**
     * Handle HTTP request validation and authorization
     */
    public function asController(Request $request): JsonResponse
    {
        Gate::authorize('view-audit-logs');

        $validated = $request->validate([
            'causer_id' => ['nullable', 'integer'],
            'event' => ['nullable', 'string', Rule::in(['created', 'updated', 'deleted'])],
            'subject_type' => ['nullable', 'string'],
            'subject_id' => ['nullable', 'integer'],
            'log_name' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search_query' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $perPage = $validated['per_page'] ?? config('audit-logging.search.default_per_page', 50);
        unset($validated['per_page']);

        $logs = $this->handle($validated, $perPage);

        // Convert items to ensure backward compatibility with Spatie Activity models
        $items = $logs->getCollection()->map(function ($log) {
            return $this->adapter->convertToSpatieActivity($log);
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}