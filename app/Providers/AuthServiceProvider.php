<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\Core\Models\Tenant;
use App\Domains\Core\Policies\TenantPolicy;
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
        //
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
        Gate::define('impersonate-tenant', function ($user, $tenant) {
            // For now, only admins can impersonate tenants
            // TODO: Replace with spatie/laravel-permission check when implemented
            return $user->isAdmin();
        });
    }
}
