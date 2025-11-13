<?php

declare(strict_types=1);

namespace Nexus\Erp\SettingsManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create Setting Request
 *
 * Validates data for creating a new setting.
 */
class CreateSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'key' => [
                'required',
                'string',
                'max:' . config('settings-management.max_key_length', 255),
                'regex:' . config('settings-management.key_pattern', '/^[a-z0-9._-]+$/i'),
            ],
            'value' => ['required'],
            'type' => [
                'required',
                'string',
                Rule::in(config('settings-management.supported_types', [])),
            ],
            'scope' => [
                'required',
                'string',
                Rule::in(['system', 'tenant', 'module', 'user']),
            ],
            'module_name' => [
                'nullable',
                'string',
                'max:100',
                'required_if:scope,module',
            ],
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                'required_if:scope,user',
            ],
            'metadata' => [
                'nullable',
                'array',
            ],
            'metadata.label' => ['nullable', 'string', 'max:255'],
            'metadata.description' => ['nullable', 'string', 'max:1000'],
            'metadata.category' => ['nullable', 'string', 'max:100'],
            'metadata.validation' => ['nullable', 'array'],
            'metadata.default' => ['nullable'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'key.required' => 'Setting key is required.',
            'key.regex' => 'Setting key must contain only alphanumeric characters, dots, underscores, and hyphens.',
            'type.in' => 'Invalid setting type. Must be one of: ' . implode(', ', config('settings-management.supported_types', [])),
            'scope.in' => 'Invalid scope. Must be one of: system, tenant, module, user.',
            'module_name.required_if' => 'Module name is required for module-scoped settings.',
            'user_id.required_if' => 'User ID is required for user-scoped settings.',
        ];
    }
}
