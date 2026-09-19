<?php

namespace App\Http\Requests\Partner;

use App\Enums\BusinessType;
use App\Services\PhoneVerificationService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterPartnerRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // A signed-in traveller upgrades their own account: no second email or password.
        $upgrading = $this->user() !== null;

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => $upgrading
                ? ['nullable', 'string', 'email:rfc', 'max:255']
                : ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:40'],
            'password' => $upgrading
                ? ['nullable']
                : ['required', 'confirmed', Password::defaults()],
            'business_name' => ['required', 'string', 'max:160'],
            'type' => ['required', Rule::enum(BusinessType::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'district' => ['nullable', 'string', 'max:120'],
            'website' => ['nullable', 'string', 'max:2048'],
            'cover_image' => ['nullable', 'string', 'max:2048'],
            'instagram' => ['nullable', 'string', 'max:2048'],
            'facebook' => ['nullable', 'string', 'max:2048'],
            'tiktok' => ['nullable', 'string', 'max:2048'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'This email already has a BookTrips account. Sign in, then use the partner application again to upgrade it.',
            'phone.required' => 'We need a mobile number to verify before your application is accepted.',
            'terms.accepted' => 'Please accept the Terms of Service and the Privacy Policy to apply.',
        ];
    }

    /**
     * Rules that need more than one field to judge.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->hasReachableProfile()) {
                    $validator->errors()->add(
                        'socials',
                        'Add at least one of website, Instagram, Facebook, TikTok or WhatsApp so travellers can find you.',
                    );
                }

                $phones = app(PhoneVerificationService::class);
                $submitted = $phones->normalise((string) $this->input('phone'));

                if ($submitted === null || $phones->sessionVerifiedPhone() !== $submitted) {
                    $validator->errors()->add(
                        'phone',
                        'Verify this mobile number with the code we text you before submitting.',
                    );
                }
            },
        ];
    }

    /**
     * At least one public profile so BookTrips can vet the business.
     */
    private function hasReachableProfile(): bool
    {
        foreach (['website', 'instagram', 'facebook', 'tiktok', 'whatsapp'] as $field) {
            if (trim((string) $this->input($field)) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => is_string($this->email) ? mb_strtolower(trim($this->email)) : $this->email,
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'business_name' => is_string($this->business_name) ? trim($this->business_name) : $this->business_name,
            'city' => is_string($this->city) ? trim($this->city) : $this->city,
        ]);
    }
}
