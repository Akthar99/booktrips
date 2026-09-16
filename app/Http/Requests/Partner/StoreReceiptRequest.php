<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;

class StoreReceiptRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxKb = (int) config('booktrips.uploads.max_receipt_kb');

        return [
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', "max:{$maxKb}"],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
