<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;

class PasswordController extends Controller
{
    /**
     * Change the signed-in user's password.
     */
    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'password' => $request->string('password')->value(),
        ])->save();

        return back()->with('success', 'Password updated.');
    }
}
