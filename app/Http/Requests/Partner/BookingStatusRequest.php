<?php

namespace App\Http\Requests\Partner;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookingStatusRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(array_column(BookingStatus::partnerSettable(), 'value'))],
        ];
    }
}
