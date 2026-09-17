<?php

namespace App\Http\Requests\Booking;

use App\Services\PhoneVerificationService;
use Illuminate\Contracts\Validation\Validator;
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
     * The contact number must pass the SMS code check before the request is sent.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $phones = app(PhoneVerificationService::class);
                $submitted = $phones->normalise((string) $this->input('guest_phone'));

                if ($submitted === null || $phones->sessionVerifiedPhone(PhoneVerificationService::PURPOSE_BOOKING) !== $submitted) {
                    $validator->errors()->add(
                        'guest_phone',
                        'Verify the mobile number with the code we text you before sending the request.',
                    );
                }
            },
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
