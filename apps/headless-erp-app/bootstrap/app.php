<?php

declare(strict_types=1);

use App\Domains\Core\Middleware\IdentifyTenant;
use App\Http\Middleware\EnsureAccountNotLocked;
use App\Http\Middleware\ValidateSanctumToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register middleware aliases for convenient use in routes
        $middleware->alias([
            'auth.locked' => EnsureAccountNotLocked::class,
            'sanctum.validate' => ValidateSanctumToken::class,
        ]);

        // Register middleware for API routes
        // This runs after auth:sanctum middleware to ensure user is authenticated first
        $middleware->api(append: [
            IdentifyTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
