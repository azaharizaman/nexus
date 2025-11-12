<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SettingsManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Bulk Update Settings Request
 *
 * Validates data for bulk updating multiple settings.
 */
class BulkUpdateSettingsRequest extends FormRequest
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
            'settings' => [
                'required',
                'array',
                'min:1',
            ],
            'settings.*.key' => [
                'required',
                'string',
                'max:' . config('settings-management.max_key_length', 255),
            ],
            'settings.*.value' => ['required'],
            'settings.*.type' => [
                'nullable',
                'string',
                Rule::in(config('settings-management.supported_types', [])),
            ],
            'settings.*.metadata' => ['nullable', 'array'],
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
            'settings.required' => 'Settings array is required.',
            'settings.*.key.required' => 'Each setting must have a key.',
            'settings.*.value.required' => 'Each setting must have a value.',
            'settings.*.type.in' => 'Invalid setting type.',
        ];
    }
}
