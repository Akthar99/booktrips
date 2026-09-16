<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * The account page: profile, password and email change.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('account/index', [
            'bookingCount' => $user->bookings()->count(),
            'reviewCount' => $user->reviews()->count(),
        ]);
    }

    /**
     * Update name and phone.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'name' => $request->string('name')->value(),
            'phone' => $request->filled('phone') ? $request->string('phone')->value() : null,
        ])->save();

        return back()->with('success', 'Profile updated.');
    }
}
