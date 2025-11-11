<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Contracts\ActivityLoggerContract;
use App\Support\Services\Logging\SpatieActivityLogger;
use Illuminate\Support\ServiceProvider;

/**
 * Logging Service Provider
 *
 * Registers activity logging service bindings.
 * Abstracts the activity logging implementation from business logic.
 */
class LoggingServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        $this->app->singleton(ActivityLoggerContract::class, function ($app) {
            $driver = config('packages.activity_logger', 'spatie');

            return match ($driver) {
                'spatie' => new SpatieActivityLogger,
                // Future implementations can be added here:
                // 'database' => new DatabaseActivityLogger(),
                // 'null' => new NullActivityLogger(),
                default => new SpatieActivityLogger,
            };
        });
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        //
    }
}
