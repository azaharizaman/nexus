<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Support\Contracts\TokenServiceContract;
use App\Support\Services\Auth\SanctumTokenService;
use Azaharizaman\Erp\Core\Models\Tenant;
use Azaharizaman\Erp\Core\Policies\TenantPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

/**
 * Auth Service Provider
 *
 * Registers authorization policies and gates for the application.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Tenant::class => TenantPolicy::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind TokenServiceContract to implementation
        $this->app->singleton(TokenServiceContract::class, function ($app) {
            $driver = config('packages.token_service', 'sanctum');

            return match ($driver) {
                'sanctum' => new SanctumTokenService,
                // Future implementations can be added here:
                // 'jwt' => new JwtTokenService(),
                // 'session' => new SessionTokenService(),
                default => new SanctumTokenService,
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register policies defined in the $policies array
        $this->registerPolicies();

        // Define gate for tenant impersonation
        // Only users with super admin privileges can impersonate tenants
        Gate::define('impersonate-tenant', function (User $user, Tenant $tenant): bool {
            // For now, only admins can impersonate tenants
            // TODO: Replace with spatie/laravel-permission check when implemented
            return $user->isAdmin();
        });
    }
}
