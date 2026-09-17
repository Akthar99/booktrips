<?php

namespace App\Http\Requests\Dispute;

use App\Enums\DisputeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpenDisputeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(DisputeType::class)],
            'summary' => ['required', 'string', 'max:160'],
            'details' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'summary.required' => 'Give the report a short headline so both sides know what it is about.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'summary' => is_string($this->summary) ? trim($this->summary) : $this->summary,
        ]);
    }
}
