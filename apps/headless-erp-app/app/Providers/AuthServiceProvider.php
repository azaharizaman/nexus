<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Support\Contracts\TokenServiceContract;
use App\Support\Services\Auth\SanctumTokenService;
use Azaharizaman\Erp\Core\Models\Tenant;
use Azaharizaman\Erp\Core\Policies\TenantPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

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
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
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

        // Super-admin bypass: Grant all permissions to super-admin
        Gate::before(function (User $user, string $ability): ?bool {
            if ($user->hasRole('super-admin')) {
                return true; // Super-admin can do everything
            }

            return null; // Continue with normal authorization checks
        });

        // Define gates for permission management
        Gate::define('manage-roles', fn (User $user): bool => $user->hasPermissionTo('manage-roles'));
        Gate::define('manage-permissions', fn (User $user): bool => $user->hasPermissionTo('manage-permissions'));
        Gate::define('assign-roles', fn (User $user): bool => $user->hasPermissionTo('assign-roles'));

        // Define gate for tenant impersonation
        Gate::define('impersonate-tenant', function (User $user, Tenant $tenant): bool {
            return $user->hasRole('super-admin') || $user->hasPermissionTo('impersonate-tenants');
        });

        // Define gate for user impersonation
        Gate::define('impersonate-user', fn (User $user): bool => $user->hasRole('super-admin'));
    }
}
