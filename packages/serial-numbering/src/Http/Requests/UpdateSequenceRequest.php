<?php

declare(strict_types=1);

namespace Azaharizaman\Erp\SerialNumbering\Http\Requests;

use Azaharizaman\Erp\SerialNumbering\Enums\ResetPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Sequence Request
 *
 * Validation for updating an existing sequence configuration.
 */
class UpdateSequenceRequest extends FormRequest
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
        return [
            'pattern' => [
                'sometimes',
                'required',
                'string',
                'max:500',
            ],
            'reset_period' => [
                'sometimes',
                'required',
                'string',
                Rule::in(ResetPeriod::values()),
            ],
            'padding' => [
                'sometimes',
                'required',
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
            'pattern.required' => 'Pattern is required.',
            'pattern.max' => 'Pattern cannot exceed 500 characters.',
            'padding.min' => 'Padding must be at least 1.',
            'padding.max' => 'Padding cannot exceed 10.',
        ];
    }
}
