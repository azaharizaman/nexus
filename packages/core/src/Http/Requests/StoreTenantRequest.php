<?php

declare(strict_types=1);

namespace Nexus\Erp\Core\Http\Requests;

use Nexus\Erp\Core\Enums\TenantStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Tenant Request
 *
 * Validates data for creating a new tenant.
 * Requires 'create-tenant' permission.
 */
class StoreTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has permission to create tenants
        return $this->user()->can('create-tenant');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255', 'unique:tenants,domain'],
            'status' => ['nullable', 'string', Rule::in(TenantStatus::values())],
            'configuration' => ['nullable', 'array'],
            'subscription_plan' => ['nullable', 'string', 'max:255'],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
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
            'name.required' => 'The tenant name is required.',
            'domain.required' => 'The tenant domain is required.',
            'domain.unique' => 'This domain is already registered to another tenant.',
            'contact_email.required' => 'A contact email is required.',
            'contact_email.email' => 'Please provide a valid contact email address.',
        ];
    }
}
