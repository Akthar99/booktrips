<?php

namespace App\Http\Requests\Partner;

use App\Enums\BusinessType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:160'],
            'type' => ['sometimes', 'required', Rule::enum(BusinessType::class)],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'required', 'string', 'max:120'],
            'district' => ['sometimes', 'nullable', 'string', 'max:120'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'website' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'cover_image' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'instagram' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'facebook' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'tiktok' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'whatsapp' => ['sometimes', 'nullable', 'string', 'max:40'],
        ];
    }
}
