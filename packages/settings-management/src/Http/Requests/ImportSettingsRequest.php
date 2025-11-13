<?php

declare(strict_types=1);

namespace Nexus\Erp\SettingsManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Import Settings Request
 *
 * Validates data for importing settings from file.
 */
class ImportSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by Gate
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:json,csv,txt',
                'max:2048', // 2MB max
            ],
            'scope' => [
                'nullable',
                'string',
                'in:system,tenant,module,user',
            ],
            'overwrite' => [
                'nullable',
                'boolean',
            ],
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
            'file.required' => 'Settings file is required.',
            'file.mimes' => 'File must be JSON or CSV format.',
            'file.max' => 'File size must not exceed 2MB.',
        ];
    }
}
