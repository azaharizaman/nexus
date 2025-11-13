<?php

declare(strict_types=1);

use Nexus\Erp\AuditLogging\Http\Controllers\AuditLogController;
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
                Route::get('/', [AuditLogController::class, 'index'])
                    ->name('index');

                // Get audit log statistics
                Route::get('/statistics', [AuditLogController::class, 'statistics'])
                    ->name('statistics');

                // Show specific audit log
                Route::get('/{id}', [AuditLogController::class, 'show'])
                    ->name('show')
                    ->where('id', '[0-9]+');

                // Export audit logs
                Route::post('/export', [AuditLogController::class, 'export'])
                    ->name('export');
            });
    });
