<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SettingsManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Setting Request
 *
 * Validates data for updating an existing setting.
 */
class UpdateSettingRequest extends FormRequest
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
            'value' => ['sometimes', 'required'],
            'type' => [
                'sometimes',
                'required',
                'string',
                Rule::in(config('settings-management.supported_types', [])),
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
            'type.in' => 'Invalid setting type. Must be one of: ' . implode(', ', config('settings-management.supported_types', [])),
        ];
    }
}
