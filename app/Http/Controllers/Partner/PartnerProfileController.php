<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Partner\UpdateBusinessRequest;
use Illuminate\Http\RedirectResponse;

class PartnerProfileController extends Controller
{
    /**
     * Update the partner's business profile.
     */
    public function update(UpdateBusinessRequest $request): RedirectResponse
    {
        $business = $request->user()->business;

        $this->authorize('update', $business);

        $allowed = [
            'name', 'type', 'description', 'address', 'city', 'district',
            'phone', 'website', 'cover_image', 'instagram', 'facebook', 'tiktok', 'whatsapp',
        ];

        $patch = [];

        foreach ($allowed as $key) {
            if ($request->exists($key)) {
                $patch[$key] = $request->input($key);
            }
        }

        $business->fill($patch)->save();

        return back()->with('success', 'Business profile updated.');
    }
}
