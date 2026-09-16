<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePackageRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->packageRules(false);
    }

    /**
     * Extra business rules that depend on several fields together.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = (string) $this->input('discount_type', 'none');
            $value = (float) $this->input('discount_value', 0);
            $price = (int) $this->input('price_lkr', 0);

            if ($type === 'percentage' && $value > 100) {
                $validator->errors()->add('discount_value', 'Percentage discount cannot be more than 100%.');
            }

            if ($type === 'fixed' && $price > 0 && $value > $price) {
                $validator->errors()->add('discount_value', 'Fixed discount cannot be greater than the package price.');
            }

            if ($this->filled('schedule_end') && $this->filled('schedule_start') && $this->input('schedule_end') < $this->input('schedule_start')) {
                $validator->errors()->add('schedule_end', 'The availability window ends before it starts.');
            }

            if ($this->filled('discount_end') && $this->filled('discount_start') && $this->input('discount_end') < $this->input('discount_start')) {
                $validator->errors()->add('discount_end', 'The discount window ends before it starts.');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function packageRules(bool $updating): array
    {
        $categories = array_column(config('booktrips.categories'), 'slug');
        $maxImages = (int) config('booktrips.uploads.max_images');

        $r = fn (bool $sometimes, array $rules): array => $sometimes ? ['sometimes', ...$rules] : $rules;

        return [
            'title' => $r($updating, ['required', 'string', 'max:160']),
            'category' => $r($updating, ['required', Rule::in($categories)]),
            'description' => $r($updating, ['required', 'string', 'max:5000']),
            'highlight' => ['nullable', 'string', 'max:500'],
            'location' => $r($updating, ['required', 'string', 'max:160']),
            'address' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:120'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'schedule_type' => $r($updating, ['required', Rule::in(['always', 'range'])]),
            'schedule_start' => ['nullable', 'date_format:Y-m-d'],
            'schedule_end' => ['nullable', 'date_format:Y-m-d'],
            'weekdays' => ['nullable', 'array', 'max:7'],
            'weekdays.*' => ['integer', 'between:0,6', 'distinct'],
            'duration_days' => $r($updating, ['required', 'integer', 'between:1,60']),
            'duration_nights' => $r($updating, ['required', 'integer', 'between:0,60']),
            'price_lkr' => $r($updating, ['required', 'integer', 'between:1,100000000']),
            'price_type' => $r($updating, ['required', Rule::in(['per_package', 'per_person', 'per_night'])]),
            'discount_type' => $r($updating, ['required', Rule::in(['none', 'percentage', 'fixed'])]),
            'discount_value' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'discount_enabled' => ['nullable', 'boolean'],
            'discount_start' => ['nullable', 'date_format:Y-m-d'],
            'discount_end' => ['nullable', 'date_format:Y-m-d'],
            'min_guests' => $r($updating, ['required', 'integer', 'between:1,100']),
            'max_guests' => $r($updating, ['required', 'integer', 'between:1,100', 'gte:min_guests']),
            'included' => ['nullable', 'array', 'max:30'],
            'included.*' => ['string', 'max:200'],
            'excluded' => ['nullable', 'array', 'max:30'],
            'excluded.*' => ['string', 'max:200'],
            'itinerary' => ['nullable', 'array', 'max:30'],
            'itinerary.*.day' => ['required_with:itinerary', 'integer', 'between:1,60'],
            'itinerary.*.title' => ['required_with:itinerary', 'string', 'max:160'],
            'itinerary.*.description' => ['nullable', 'string', 'max:1000'],
            'amenities' => ['nullable', 'array', 'max:30'],
            'amenities.*' => ['string', 'max:200'],
            'images' => ['nullable', 'array', "max:{$maxImages}"],
            'images.*' => ['string', 'max:2048'],
            'meeting_point' => ['nullable', 'string', 'max:255'],
            'cancellation_policy' => ['nullable', 'string', 'max:2000'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
