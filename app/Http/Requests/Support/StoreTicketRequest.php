<?php

namespace App\Http\Requests\Support;

use App\Enums\TicketCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:160'],
            'category' => ['required', Rule::enum(TicketCategory::class)],
            'body' => ['required', 'string', 'min:15', 'max:4000'],
            'booking_id' => ['nullable', 'integer', 'exists:bookings,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.min' => 'Add a little more detail so we can help straight away.',
        ];
    }
}
