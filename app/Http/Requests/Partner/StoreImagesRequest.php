<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;

class StoreImagesRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxImages = (int) config('booktrips.uploads.max_images');
        $maxKb = (int) config('booktrips.uploads.max_image_kb');

        return [
            'images' => ['required', 'array', 'min:1', "max:{$maxImages}"],
            'images.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', "max:{$maxKb}"],
        ];
    }
}
