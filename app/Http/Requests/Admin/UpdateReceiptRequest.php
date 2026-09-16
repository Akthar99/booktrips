<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReceiptStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReceiptRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(array_column(ReceiptStatus::cases(), 'value'))],
        ];
    }
}
