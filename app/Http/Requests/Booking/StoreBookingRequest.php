<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'check_in' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'check_out' => ['required', 'date_format:Y-m-d', 'after_or_equal:check_in'],
            'guests' => ['required', 'integer', 'min:1', 'max:50'],
            'guest_name' => ['required', 'string', 'max:120'],
            'guest_phone' => ['required', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'guest_name' => is_string($this->guest_name) ? trim($this->guest_name) : $this->guest_name,
            'guest_phone' => is_string($this->guest_phone) ? trim($this->guest_phone) : $this->guest_phone,
            'notes' => is_string($this->notes) ? trim($this->notes) : $this->notes,
        ]);
    }
}
