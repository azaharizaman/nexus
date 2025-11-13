<?php

declare(strict_types=1);

use Nexus\Erp\SerialNumbering\Http\Controllers\SequenceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Serial Numbering API Routes
|--------------------------------------------------------------------------
|
| Routes for sequence management and serial number generation.
| All routes require authentication and tenant context.
|
*/

Route::middleware(['api', 'auth:sanctum', 'tenant.context'])
    ->prefix('api/v1/sequences')
    ->group(function () {
        // List all sequences for tenant
        Route::get('/', [SequenceController::class, 'index'])
            ->name('api.v1.sequences.index');

        // Create new sequence
        Route::post('/', [SequenceController::class, 'store'])
            ->name('api.v1.sequences.store');

        // Get specific sequence
        Route::get('/{sequenceName}', [SequenceController::class, 'show'])
            ->name('api.v1.sequences.show');

        // Update sequence configuration
        Route::patch('/{sequenceName}', [SequenceController::class, 'update'])
            ->name('api.v1.sequences.update');

        // Delete sequence
        Route::delete('/{sequenceName}', [SequenceController::class, 'destroy'])
            ->name('api.v1.sequences.destroy');

        // Generate new serial number
        Route::post('/{sequenceName}/generate', [SequenceController::class, 'generate'])
            ->name('api.v1.sequences.generate');

        // Preview next serial number
        Route::get('/{sequenceName}/preview', [SequenceController::class, 'preview'])
            ->name('api.v1.sequences.preview');

        // Reset sequence counter
        Route::post('/{sequenceName}/reset', [SequenceController::class, 'reset'])
            ->name('api.v1.sequences.reset');

        // Override serial number
        Route::post('/{sequenceName}/override', [SequenceController::class, 'override'])
            ->name('api.v1.sequences.override');
    });
