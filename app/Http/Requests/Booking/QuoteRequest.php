<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class QuoteRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'check_in' => ['required', 'date_format:Y-m-d'],
            'check_out' => ['required', 'date_format:Y-m-d', 'after_or_equal:check_in'],
            'guests' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }
}
