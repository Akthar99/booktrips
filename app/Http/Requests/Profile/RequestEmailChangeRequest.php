<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestEmailChangeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                'different:current_email',
                Rule::unique('users', 'email'),
            ],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.different' => 'That is already your email.',
            'email.unique' => 'That email is already in use.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => is_string($this->email) ? mb_strtolower(trim($this->email)) : $this->email,
            'current_email' => $this->user()?->email,
        ]);
    }
}
