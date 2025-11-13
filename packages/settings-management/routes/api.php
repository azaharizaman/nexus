<?php

declare(strict_types=1);

use Nexus\Erp\SettingsManagement\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Settings Management API Routes
|--------------------------------------------------------------------------
|
| API routes for settings management with authentication and tenant middleware.
|
*/

Route::middleware(['auth:sanctum', 'tenant'])
    ->prefix('api/v1/settings')
    ->name('api.v1.settings.')
    ->group(function () {
        // List all settings for a scope
        Route::get('/', [SettingsController::class, 'index'])
            ->name('index');

        // Get a specific setting by key
        Route::get('/{key}', [SettingsController::class, 'show'])
            ->name('show')
            ->where('key', '.*'); // Allow dots in key parameter

        // Create a new setting
        Route::post('/', [SettingsController::class, 'store'])
            ->name('store');

        // Update a setting
        Route::patch('/{key}', [SettingsController::class, 'update'])
            ->name('update')
            ->where('key', '.*');

        // Delete a setting
        Route::delete('/{key}', [SettingsController::class, 'destroy'])
            ->name('destroy')
            ->where('key', '.*');

        // Bulk update multiple settings
        Route::post('/bulk', [SettingsController::class, 'bulk'])
            ->name('bulk');

        // Export settings
        Route::get('/export', [SettingsController::class, 'export'])
            ->name('export');

        // Import settings
        Route::post('/import', [SettingsController::class, 'import'])
            ->name('import');
    });
