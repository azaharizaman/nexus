<?php

declare(strict_types=1);

use Nexus\Erp\Domains\Core\Middleware\IdentifyTenant;
use Nexus\Erp\Exceptions\UnitOfMeasure\IncompatibleUomException;
use Nexus\Erp\Exceptions\UnitOfMeasure\InvalidQuantityException;
use Nexus\Erp\Exceptions\UnitOfMeasure\UomConversionException;
use Nexus\Erp\Exceptions\UnitOfMeasure\UomNotFoundException;
use Nexus\Erp\Http\Middleware\EnsureAccountNotLocked;
use Nexus\Erp\Http\Middleware\ValidateSanctumToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // web: __DIR__ . '/../routes/web.php',  // Disabled - Edward is terminal-only
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
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
        // Register UOM exception handlers
        $exceptions->renderable(function (UomConversionException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => class_basename($e),
                    'message' => $e->getMessage(),
                    'code' => $e->getHttpStatusCode(),
                ], $e->getHttpStatusCode());
            }
        });

        $exceptions->renderable(function (IncompatibleUomException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'IncompatibleUomException',
                    'message' => $e->getMessage(),
                    'from_category' => $e->getFromCategory(),
                    'to_category' => $e->getToCategory(),
                    'code' => $e->getHttpStatusCode(),
                ], $e->getHttpStatusCode());
            }
        });

        $exceptions->renderable(function (UomNotFoundException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'UomNotFoundException',
                    'message' => $e->getMessage(),
                    'uom_code' => $e->getUomCode(),
                    'code' => $e->getHttpStatusCode(),
                ], $e->getHttpStatusCode());
            }
        });

        $exceptions->renderable(function (InvalidQuantityException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'InvalidQuantityException',
                    'message' => $e->getMessage(),
                    'quantity' => $e->getQuantity(),
                    'reason' => $e->getReason(),
                    'code' => $e->getHttpStatusCode(),
                ], $e->getHttpStatusCode());
            }
        });
    })->create();
