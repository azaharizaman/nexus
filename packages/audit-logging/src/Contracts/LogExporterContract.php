<?php

declare(strict_types=1);

namespace Nexus\Erp\AuditLogging\Contracts;

use Illuminate\Database\Eloquent\Collection;

/**
 * Log Exporter Contract
 *
 * Defines the interface for exporting audit logs to various formats.
 */
interface LogExporterContract
{
    /**
     * Export audit logs to CSV format
     *
     * Generates a CSV file with columns:
     * - ID
     * - Tenant
     * - Event
     * - Actor (User/System)
     * - Subject Type
     * - Subject ID
     * - Description
     * - IP Address
     * - Created At
     *
     * @param  Collection  $logs  Collection of activity logs
     * @param  string  $filename  Output filename
     * @return string Path to generated CSV file
     */
    public function exportToCsv(Collection $logs, string $filename): string;

    /**
     * Export audit logs to JSON format
     *
     * Exports full log data including properties in JSON format.
     *
     * @param  Collection  $logs  Collection of activity logs
     * @param  string  $filename  Output filename
     * @return string Path to generated JSON file
     */
    public function exportToJson(Collection $logs, string $filename): string;

    /**
     * Export audit logs to PDF format
     *
     * Generates a formatted PDF report with logo, headers, and styled table.
     *
     * @param  Collection  $logs  Collection of activity logs
     * @param  string  $filename  Output filename
     * @param  array<string, mixed>  $options  PDF options (title, subtitle, etc.)
     * @return string Path to generated PDF file
     */
    public function exportToPdf(Collection $logs, string $filename, array $options = []): string;

    /**
     * Get export file path
     *
     * Returns the full path where export files are stored.
     *
     * @param  string  $filename  Filename
     * @return string Full file path
     */
    public function getExportPath(string $filename): string;

    /**
     * Clean up old export files
     *
     * Removes export files older than specified hours.
     *
     * @param  int  $olderThanHours  Delete files older than this many hours
     * @return int Number of files deleted
     */
    public function cleanupOldExports(int $olderThanHours = 24): int;
}
