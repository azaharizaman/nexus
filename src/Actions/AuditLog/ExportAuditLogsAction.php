<?php

declare(strict_types=1);

namespace Nexus\Erp\Actions\AuditLog;

use Lorisleiva\Actions\Concerns\AsAction;
use Nexus\AuditLog\Contracts\AuditLogRepositoryContract;
use Nexus\AuditLog\Contracts\LogExporterContract;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\Models\Activity;

/**
 * Export Audit Logs Action
 *
 * Exports audit logs in various formats (CSV, JSON, PDF).
 */
class ExportAuditLogsAction
{
    use AsAction;

    public function __construct(
        protected AuditLogRepositoryContract $repository,
        protected LogExporterContract $exporter
    ) {}

    /**
     * Handle the audit log export
     *
     * @param array $filters Export filters
     * @param string $format Export format (csv, json, pdf)
     * @param int $maxRecords Maximum records to export
     * @return string File path of exported file
     */
    public function handle(array $filters, string $format, int $maxRecords = 10000): string
    {
        // Auto-inject tenant_id from authenticated user
        if (auth()->check() && isset(auth()->user()->tenant_id)) {
            $filters['tenant_id'] = auth()->user()->tenant_id;
        }

        // Export logs
        $logs = $this->repository->export($filters, $maxRecords);

        // Generate filename
        $filename = 'audit-logs-'.now()->format('Y-m-d-His');

        // Export based on format
        $filePath = match ($format) {
            'csv' => $this->exporter->exportToCsv($logs, $filename.'.csv'),
            'json' => $this->exporter->exportToJson($logs, $filename.'.json'),
            'pdf' => $this->exporter->exportToPdf($logs, $filename.'.pdf'),
            default => throw new \InvalidArgumentException('Invalid export format'),
        };

        // Log the export action itself using the internal audit log repository
        if (auth()->check()) {
            $this->repository->create([
                'log_name' => 'default',
                'description' => 'Exported audit logs',
                'causer_type' => get_class(auth()->user()),
                'causer_id' => auth()->id(),
                'properties' => [
                    'format' => $format,
                    'filters' => $filters,
                    'record_count' => $logs->count(),
                ],
            ]);
        }

        return $filePath;
    }

    /**
     * Handle HTTP request with validation and authorization
     */
    public function asController(Request $request): BinaryFileResponse
    {
        Gate::authorize('audit-log.export');

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

        // Get max records based on format
        $maxRecords = $validated['max_records'] ?? match ($format) {
            'csv' => config('audit-logging.export.max_records_csv', 100000),
            'json' => config('audit-logging.export.max_records_json', 50000),
            'pdf' => config('audit-logging.export.max_records_pdf', 10000),
            default => 10000,
        };

        unset($validated['max_records']);

        $filePath = $this->handle($validated, $format, $maxRecords);

        // Return file download
        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}