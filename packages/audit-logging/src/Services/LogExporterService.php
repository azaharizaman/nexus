<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\AuditLogging\Services;

use Azaharizaman\Erp\AuditLogging\Contracts\LogExporterContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

/**
 * Log Exporter Service
 *
 * Handles export of audit logs to various formats (CSV, JSON, PDF).
 */
class LogExporterService implements LogExporterContract
{
    /**
     * Export audit logs to CSV format
     *
     * @param  Collection  $logs  Collection of activity logs
     * @param  string  $filename  Output filename
     * @return string Path to generated CSV file
     */
    public function exportToCsv(Collection $logs, string $filename): string
    {
        $filePath = $this->getExportPath($filename);

        // Create CSV writer
        $csv = Writer::createFromPath($filePath, 'w+');

        // Add CSV headers
        $csv->insertOne([
            'ID',
            'Tenant ID',
            'Log Name',
            'Event',
            'Description',
            'Actor Type',
            'Actor ID',
            'Actor Name',
            'Subject Type',
            'Subject ID',
            'IP Address',
            'User Agent',
            'Request ID',
            'Created At',
        ]);

        // Add data rows
        foreach ($logs as $log) {
            $csv->insertOne([
                $log->id,
                $log->tenant_id ?? 'N/A',
                $log->log_name,
                $log->event ?? 'N/A',
                $log->description,
                $log->causer_type ?? 'System',
                $log->causer_id ?? 'N/A',
                $this->getCauserName($log),
                $log->subject_type ?? 'N/A',
                $log->subject_id ?? 'N/A',
                $log->ip_address ?? 'N/A',
                $log->user_agent ? substr($log->user_agent, 0, 100) : 'N/A',
                $log->request_id ?? 'N/A',
                $log->created_at?->toDateTimeString() ?? 'N/A',
            ]);
        }

        return $filePath;
    }

    /**
     * Export audit logs to JSON format
     *
     * @param  Collection  $logs  Collection of activity logs
     * @param  string  $filename  Output filename
     * @return string Path to generated JSON file
     */
    public function exportToJson(Collection $logs, string $filename): string
    {
        $filePath = $this->getExportPath($filename);

        $data = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'tenant_id' => $log->tenant_id,
                'log_name' => $log->log_name,
                'event' => $log->event,
                'description' => $log->description,
                'causer' => [
                    'type' => $log->causer_type,
                    'id' => $log->causer_id,
                    'name' => $this->getCauserName($log),
                ],
                'subject' => [
                    'type' => $log->subject_type,
                    'id' => $log->subject_id,
                ],
                'properties' => $log->properties,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'request_id' => $log->request_id,
                'created_at' => $log->created_at?->toIso8601String(),
            ];
        });

        // Write JSON to file
        file_put_contents($filePath, json_encode([
            'exported_at' => now()->toIso8601String(),
            'total_records' => $logs->count(),
            'data' => $data,
        ], JSON_PRETTY_PRINT));

        return $filePath;
    }

    /**
     * Export audit logs to PDF format
     *
     * @param  Collection  $logs  Collection of activity logs
     * @param  string  $filename  Output filename
     * @param  array<string, mixed>  $options  PDF options
     * @return string Path to generated PDF file
     */
    public function exportToPdf(Collection $logs, string $filename, array $options = []): string
    {
        $filePath = $this->getExportPath($filename);

        // Generate HTML content for PDF
        $html = $this->generatePdfHtml($logs, $options);

        // Check if DomPDF is available
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            $pdf->save($filePath);
        } else {
            // Fallback: save as HTML if PDF library not available
            $htmlPath = str_replace('.pdf', '.html', $filePath);
            file_put_contents($htmlPath, $html);

            return $htmlPath;
        }

        return $filePath;
    }

    /**
     * Get export file path
     *
     * @param  string  $filename  Filename
     * @return string Full file path
     */
    public function getExportPath(string $filename): string
    {
        $disk = Storage::disk(config('audit-logging.export.disk', 'local'));
        $path = config('audit-logging.export.path', 'exports/audit-logs');

        // Ensure directory exists with secure permissions
        $fullPath = storage_path('app/'.$path);
        if (! is_dir($fullPath)) {
            mkdir($fullPath, 0700, true);
        }

        return $fullPath.'/'.$filename;
    }

    /**
     * Clean up old export files
     *
     * @param  int  $olderThanHours  Delete files older than this many hours
     * @return int Number of files deleted
     */
    public function cleanupOldExports(int $olderThanHours = 24): int
    {
        $path = config('audit-logging.export.path', 'exports/audit-logs');
        $fullPath = storage_path('app/'.$path);

        if (! is_dir($fullPath)) {
            return 0;
        }

        $cutoffTime = now()->subHours($olderThanHours)->timestamp;
        $deleted = 0;

        $files = glob($fullPath.'/*');
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoffTime) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Get causer name from log
     *
     * @param  mixed  $log  Activity log
     * @return string Causer name
     */
    protected function getCauserName(mixed $log): string
    {
        if (! $log->causer) {
            return 'System';
        }

        if (method_exists($log->causer, 'getName')) {
            return $log->causer->getName();
        }

        return $log->causer->name ?? 'Unknown';
    }

    /**
     * Generate HTML content for PDF export
     *
     * @param  Collection  $logs  Collection of activity logs
     * @param  array<string, mixed>  $options  PDF options
     * @return string HTML content
     */
    protected function generatePdfHtml(Collection $logs, array $options): string
    {
        $title = $options['title'] ?? 'Audit Log Report';
        $subtitle = $options['subtitle'] ?? 'Generated on '.now()->format('Y-m-d H:i:s');

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>'.$title.'</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        h1 { font-size: 18px; margin-bottom: 5px; }
        h2 { font-size: 12px; color: #666; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .truncate { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    </style>
</head>
<body>
    <h1>'.$title.'</h1>
    <h2>'.$subtitle.'</h2>
    <p>Total Records: '.$logs->count().'</p>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Event</th>
                <th>Description</th>
                <th>Actor</th>
                <th>Subject</th>
                <th>IP Address</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>';

        foreach ($logs as $log) {
            $html .= '<tr>
                <td>'.$log->id.'</td>
                <td>'.($log->event ?? 'N/A').'</td>
                <td class="truncate">'.htmlspecialchars($log->description).'</td>
                <td>'.$this->getCauserName($log).'</td>
                <td>'.class_basename($log->subject_type ?? 'N/A').' #'.($log->subject_id ?? 'N/A').'</td>
                <td>'.($log->ip_address ?? 'N/A').'</td>
                <td>'.$log->created_at?->format('Y-m-d H:i').'</td>
            </tr>';
        }

        $html .= '
        </tbody>
    </table>
</body>
</html>';

        return $html;
    }
}
