<?php

declare(strict_types=1);

namespace Edward\Providers;

use Nexus\Erp\Support\Contracts\UserRepositoryContract;
use Edward\Repositories\UserRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            UserRepositoryContract::class,
            UserRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application
     */
    protected function configureRateLimiting(): void
    {
        // Authentication endpoints rate limiter
        // 5 attempts per minute per email or IP
        RateLimiter::for('auth', function (Request $request): Limit {
            return Limit::perMinute(5)->by(
                $request->input('email', $request->ip())
            );
        });

        // General API rate limiter
        // 60 requests per minute per user or IP
        RateLimiter::for('api', function (Request $request): Limit {
            return $request->user()
                ? Limit::perMinute(60)->by($request->user()->id)
                : Limit::perMinute(60)->by($request->ip());
        });
    }
}
