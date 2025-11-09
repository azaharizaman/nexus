<?php

declare(strict_types=1);

namespace App\Domains\Core\Services;

use App\Domains\Core\Contracts\TenantManagerContract;
use App\Domains\Core\Enums\TenantStatus;
use App\Domains\Core\Models\Tenant;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Tenant Manager Service
 *
 * Manages tenant context switching, impersonation, and lifecycle operations.
 * Provides centralized tenant management with audit logging and validation.
 */
class TenantManager implements TenantManagerContract
{
    /**
     * The key for storing current tenant in app container
     */
    protected const TENANT_KEY = 'tenant.current';

    /**
     * The key for storing original tenant during impersonation
     */
    protected const ORIGINAL_TENANT_KEY = 'tenant.original';

    /**
     * The key for storing impersonation context
     */
    protected const IMPERSONATION_KEY = 'tenant.impersonation';

    /**
     * Create a new tenant with validation
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(array $data): Tenant
    {
        // Validate tenant data
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255', 'unique:tenants,domain'],
            'billing_email' => ['required', 'email', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'subscription_plan' => ['nullable', 'string', 'max:100'],
            'configuration' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Create tenant with validated data
        $validatedData = $validator->validated();

        // Set default status if not provided
        $validatedData['status'] = $validatedData['status'] ?? TenantStatus::ACTIVE;

        // Create tenant
        $tenant = Tenant::create($validatedData);

        // Log tenant creation
        activity()
            ->performedOn($tenant)
            ->causedBy(auth()->user())
            ->log('Tenant created');

        return $tenant;
    }

    /**
     * Set the active tenant in the current request context
     */
    public function setActive(Tenant $tenant): void
    {
        app()->instance(self::TENANT_KEY, $tenant);
    }

    /**
     * Get the current active tenant from context
     */
    public function current(): ?Tenant
    {
        return app()->has(self::TENANT_KEY) ? app(self::TENANT_KEY) : null;
    }

    /**
     * Impersonate a tenant for support operations
     */
    public function impersonate(Tenant $tenant, string $reason): void
    {
        // Store current tenant if exists
        $currentTenant = $this->current();
        if ($currentTenant !== null) {
            app()->instance(self::ORIGINAL_TENANT_KEY, $currentTenant);
        }

        // Set new tenant as active
        $this->setActive($tenant);

        // Store impersonation context
        app()->instance(self::IMPERSONATION_KEY, [
            'tenant_id' => $tenant->id,
            'reason' => $reason,
            'started_at' => now(),
            'user_id' => auth()->id(),
        ]);

        // Log impersonation for audit trail
        activity()
            ->performedOn($tenant)
            ->causedBy(auth()->user())
            ->withProperties([
                'reason' => $reason,
                'original_tenant_id' => $currentTenant?->id,
            ])
            ->log('Tenant impersonation started');
    }

    /**
     * Stop tenant impersonation and restore original context
     */
    public function stopImpersonation(): void
    {
        // Get impersonation context
        $impersonation = app()->has(self::IMPERSONATION_KEY) ? app(self::IMPERSONATION_KEY) : null;
        $currentTenant = $this->current();

        // Log impersonation end
        if ($currentTenant !== null) {
            activity()
                ->performedOn($currentTenant)
                ->causedBy(auth()->user())
                ->withProperties([
                    'duration' => isset($impersonation['started_at'])
                        ? now()->diffInSeconds($impersonation['started_at'])
                        : null,
                ])
                ->log('Tenant impersonation stopped');
        }

        // Restore original tenant
        $originalTenant = app()->has(self::ORIGINAL_TENANT_KEY) ? app(self::ORIGINAL_TENANT_KEY) : null;
        if ($originalTenant !== null) {
            $this->setActive($originalTenant);
            app()->forgetInstance(self::ORIGINAL_TENANT_KEY);
        } else {
            app()->forgetInstance(self::TENANT_KEY);
        }

        // Clear impersonation context
        app()->forgetInstance(self::IMPERSONATION_KEY);
    }
}
