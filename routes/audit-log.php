<?php

declare(strict_types=1);

use Nexus\Erp\Actions\AuditLog\SearchAuditLogsAction;
use Nexus\Erp\Actions\AuditLog\ShowAuditLogAction;
use Nexus\Erp\Actions\AuditLog\ExportAuditLogsAction;
use Nexus\Erp\Actions\AuditLog\GetAuditLogStatisticsAction;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Audit Logging API Routes
|--------------------------------------------------------------------------
|
| API routes for audit log retrieval, search, and export.
| All routes require authentication via Sanctum.
|
*/

Route::prefix('api/v1')
    ->middleware(['auth:sanctum'])
    ->name('api.v1.')
    ->group(function () {
        // Audit log routes
        Route::prefix('audit-logs')
            ->name('audit-logs.')
            ->group(function () {
                // List audit logs with filters
                Route::get('/', SearchAuditLogsAction::class)
                    ->name('index');

                // Get audit log statistics
                Route::get('/statistics', GetAuditLogStatisticsAction::class)
                    ->name('statistics');

                // Show specific audit log
                Route::get('/{id}', ShowAuditLogAction::class)
                    ->name('show')
                    ->where('id', '[0-9]+');

                // Export audit logs
                Route::post('/export', ExportAuditLogsAction::class)
                    ->name('export');
            });
    });