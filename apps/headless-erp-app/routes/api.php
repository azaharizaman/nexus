<?php

use App\Http\Controllers\Api\V1\Admin\UserManagementController;
use App\Http\Controllers\Api\V1\TenantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API Version 1
Route::prefix('v1')->name('api.v1.')->group(function () {
    // Protected routes - require authentication
    Route::middleware('auth:sanctum')->group(function () {
        // Tenant Management (Admin Only)
        Route::apiResource('tenants', TenantController::class);

        // Admin User Management
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::apiResource('users', UserManagementController::class);
            Route::post('users/{user}/suspend', [UserManagementController::class, 'suspend'])
                ->name('users.suspend');
            Route::post('users/{user}/unlock', [UserManagementController::class, 'unlock'])
                ->name('users.unlock');
        });
    });
});
