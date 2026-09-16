<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:160'],
            'comment' => ['required', 'string', 'max:1200'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => is_string($this->title) ? trim($this->title) : $this->title,
            'comment' => is_string($this->comment) ? trim($this->comment) : $this->comment,
        ]);
    }
}
