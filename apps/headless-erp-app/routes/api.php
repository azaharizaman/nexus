<?php

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
    // Tenant Management (Admin Only) - Protected by auth:sanctum middleware
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('tenants', TenantController::class);
    });
});
