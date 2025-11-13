<?php

declare(strict_types=1);

namespace Nexus\Erp\AuditLogging\Http\Controllers;

use Nexus\Erp\AuditLogging\Contracts\AuditLogRepositoryContract;
use Nexus\Erp\AuditLogging\Contracts\LogExporterContract;
use Nexus\Erp\AuditLogging\Http\Resources\AuditLogResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Audit Log Controller
 *
 * Handles API requests for audit log retrieval, search, and export.
 */
class AuditLogController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected AuditLogRepositoryContract $repository
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of audit logs with filtering and pagination.
     *
     * @param  Request  $request  The HTTP request
     * @return JsonResponse Paginated audit logs
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', \Spatie\Activitylog\Models\Activity::class);

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

        // Build filters array
        $filters = $validated;

        // Auto-inject tenant_id from authenticated user
        if (auth()->check() && isset(auth()->user()->tenant_id)) {
            $filters['tenant_id'] = auth()->user()->tenant_id;
        }

        $perPage = $filters['per_page'] ?? config('audit-logging.search.default_per_page', 50);
        unset($filters['per_page']);

        // Search audit logs
        $logs = $this->repository->search($filters, $perPage);

        return AuditLogResource::collection($logs)
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Display the specified audit log.
     *
     * @param  int  $id  Audit log ID
     * @return JsonResponse Audit log details
     */
    public function show(int $id): JsonResponse
    {
        $log = $this->repository->find($id);

        if (! $log) {
            return response()->json([
                'message' => 'Audit log not found',
            ], 404);
        }

        Gate::authorize('view', $log);

        return AuditLogResource::make($log)
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Export audit logs based on filters.
     *
     * @param  Request  $request  The HTTP request
     * @param  LogExporterContract  $exporter  Log exporter service
     * @return Response Download response with export file
     */
    public function export(Request $request, LogExporterContract $exporter): Response
    {
        Gate::authorize('export', \Spatie\Activitylog\Models\Activity::class);

        $validated = $request->validate([
            'format' => ['required', 'string', Rule::in(['csv', 'json', 'pdf'])],
            'causer_id' => ['nullable', 'integer'],
            'event' => ['nullable', 'string', Rule::in(['created', 'updated', 'deleted'])],
            'subject_type' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'max_records' => ['nullable', 'integer', 'min:1'],
        ]);

        $format = $validated['format'];
        unset($validated['format']);

        // Build filters
        $filters = $validated;

        // Auto-inject tenant_id from authenticated user
        if (auth()->check() && isset(auth()->user()->tenant_id)) {
            $filters['tenant_id'] = auth()->user()->tenant_id;
        }

        // Get max records based on format
        $maxRecords = $filters['max_records'] ?? match ($format) {
            'csv' => config('audit-logging.export.max_records_csv', 100000),
            'json' => config('audit-logging.export.max_records_json', 50000),
            'pdf' => config('audit-logging.export.max_records_pdf', 10000),
            default => 10000,
        };

        unset($filters['max_records']);

        // Export logs
        $logs = $this->repository->export($filters, $maxRecords);

        // Generate filename
        $filename = 'audit-logs-'.now()->format('Y-m-d-His');

        // Export based on format
        $filePath = match ($format) {
            'csv' => $exporter->exportToCsv($logs, $filename.'.csv'),
            'json' => $exporter->exportToJson($logs, $filename.'.json'),
            'pdf' => $exporter->exportToPdf($logs, $filename.'.pdf'),
            default => throw new \InvalidArgumentException('Invalid export format'),
        };

        // Log the export action itself
        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'format' => $format,
                'filters' => $filters,
                'record_count' => $logs->count(),
            ])
            ->log('Exported audit logs');

        // Return file download
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    /**
     * Get audit log statistics.
     *
     * @param  Request  $request  The HTTP request
     * @return JsonResponse Statistics data
     */
    public function statistics(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', \Spatie\Activitylog\Models\Activity::class);

        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'log_name' => ['nullable', 'string'],
        ]);

        $filters = $validated;

        // Auto-inject tenant_id from authenticated user
        if (auth()->check() && isset(auth()->user()->tenant_id)) {
            $filters['tenant_id'] = auth()->user()->tenant_id;
        }

        $statistics = $this->repository->getStatistics($filters);

        return response()->json([
            'data' => $statistics,
        ], 200);
    }
}
