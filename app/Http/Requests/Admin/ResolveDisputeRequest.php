<?php

namespace App\Http\Requests\Admin;

use App\Enums\DisputePenalty;
use App\Enums\DisputeResolution;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveDisputeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'resolution' => ['required', Rule::enum(DisputeResolution::class)],
            'penalty' => ['nullable', Rule::enum(DisputePenalty::class)],
            'penalty_amount_lkr' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'resolution_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'resolution.required' => 'Decide who was at fault before closing the report.',
        ];
    }
}
