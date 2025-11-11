<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Azaharizaman\Erp\Core\Enums\TenantStatus;
use Azaharizaman\Erp\Core\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Tenant Request
 *
 * Validation rules for creating a new tenant.
 */
class StoreTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Tenant::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The tenant name is required.',
            'domain.required' => 'The tenant domain is required.',
            'domain.unique' => 'This domain is already registered to another tenant.',
            'contact_email.required' => 'A contact email is required for the tenant.',
            'contact_email.email' => 'The contact email must be a valid email address.',
        ];
    }
}
