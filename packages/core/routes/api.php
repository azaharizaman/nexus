<?php

declare(strict_types=1);

use Nexus\Erp\Core\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Management API Routes
|--------------------------------------------------------------------------
|
| These routes provide RESTful API endpoints for tenant management.
| All routes require authentication via Sanctum.
|
*/

Route::prefix('api/v1')->middleware(['auth:sanctum'])->group(function () {
    // Tenant CRUD endpoints
    Route::apiResource('tenants', TenantController::class);

    // Tenant lifecycle endpoints
    Route::post('tenants/{tenant}/suspend', [TenantController::class, 'suspend'])
        ->name('tenants.suspend');
    Route::post('tenants/{tenant}/activate', [TenantController::class, 'activate'])
        ->name('tenants.activate');
    Route::post('tenants/{tenant}/archive', [TenantController::class, 'archive'])
        ->name('tenants.archive');

    // Tenant impersonation endpoints
    Route::post('tenants/{tenant}/impersonate', [TenantController::class, 'impersonate'])
        ->name('tenants.impersonate');
    Route::post('impersonation/end', [TenantController::class, 'endImpersonation'])
        ->name('impersonation.end');
});
