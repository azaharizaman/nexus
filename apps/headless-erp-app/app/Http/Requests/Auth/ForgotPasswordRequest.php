<?php

declare(strict_types=1);

namespace Nexus\Erp\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Forgot Password Request
 *
 * Validates password reset request.
 */
class ForgotPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'tenant_id' => ['required', 'string', 'uuid', 'exists:tenants,id'],
        ];
    }

    /**
     * Get custom messages for validator errors
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'tenant_id.required' => 'Tenant ID is required',
            'tenant_id.uuid' => 'Invalid tenant ID format',
        ];
    }
}
