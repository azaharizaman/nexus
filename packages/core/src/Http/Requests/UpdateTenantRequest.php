<?php

declare(strict_types=1);

namespace Nexus\Erp\Core\Http\Requests;

use Nexus\Erp\Core\Enums\TenantStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Tenant Request
 *
 * Validates data for updating an existing tenant.
 * Requires 'update-tenant' permission.
 */
class UpdateTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has permission to update tenants
        return $this->user()->can('update-tenant', $this->route('tenant'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'name' => ['nullable', 'string', 'max:255'],
            'domain' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('tenants', 'domain')->ignore($tenant->id ?? null),
            ],
            'status' => ['nullable', 'string', Rule::in(TenantStatus::values())],
            'configuration' => ['nullable', 'array'],
            'subscription_plan' => ['nullable', 'string', 'max:255'],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'domain.unique' => 'This domain is already registered to another tenant.',
            'contact_email.email' => 'Please provide a valid contact email address.',
            'billing_email.email' => 'Please provide a valid billing email address.',
        ];
    }
}
