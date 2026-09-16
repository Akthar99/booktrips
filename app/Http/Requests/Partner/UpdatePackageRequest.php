<?php

namespace App\Http\Requests\Partner;

class UpdatePackageRequest extends StorePackageRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->packageRules(true);
    }
}
