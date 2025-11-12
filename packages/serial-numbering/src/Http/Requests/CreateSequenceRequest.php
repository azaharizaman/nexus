<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SerialNumbering\Http\Requests;

use Azaharizaman\Erp\SerialNumbering\Enums\ResetPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create Sequence Request
 *
 * Validation for creating a new sequence configuration.
 */
class CreateSequenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage-sequences');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Get tenant_id from context (set by middleware)
        $tenantId = $this->route('tenant_id') ?? request()->get('tenant_id');

        return [
            'sequence_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_-]+$/',
                Rule::unique('serial_number_sequences', 'sequence_name')
                    ->where('tenant_id', $tenantId),
            ],
            'pattern' => [
                'required',
                'string',
                'max:500',
            ],
            'reset_period' => [
                'nullable',
                'string',
                Rule::in(ResetPeriod::values()),
            ],
            'padding' => [
                'nullable',
                'integer',
                'min:1',
                'max:10',
            ],
            'metadata' => [
                'nullable',
                'array',
            ],
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sequence_name.required' => 'Sequence name is required.',
            'sequence_name.regex' => 'Sequence name can only contain letters, numbers, hyphens, and underscores.',
            'sequence_name.unique' => 'A sequence with this name already exists.',
            'pattern.required' => 'Pattern is required.',
            'pattern.max' => 'Pattern cannot exceed 500 characters.',
            'padding.min' => 'Padding must be at least 1.',
            'padding.max' => 'Padding cannot exceed 10.',
        ];
    }
}
