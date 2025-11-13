<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Nexus\Erp\Core\Enums\TenantStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Tenant Request
 *
 * Validation rules for updating an existing tenant.
 */
class UpdateTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled in the controller after fetching the tenant
        // This just checks if the user is authenticated
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = $this->route('tenant');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'domain' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('tenants', 'domain')->ignore($tenantId),
            ],
            'status' => ['sometimes', 'string', Rule::in(TenantStatus::values())],
            'configuration' => ['sometimes', 'array'],
            'subscription_plan' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billing_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'contact_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contact_email' => ['sometimes', 'email', 'max:255'],
            'contact_phone' => ['sometimes', 'nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'domain.unique' => 'This domain is already registered to another tenant.',
            'contact_email.email' => 'The contact email must be a valid email address.',
        ];
    }
}
