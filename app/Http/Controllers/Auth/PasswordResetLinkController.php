<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Show the forgot-password page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/forgot-password', [
            'status' => session('status'),
        ]);
    }

    /**
     * Email a reset link. The response is identical whether the email exists or not.
     */
    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $email = $request->string('email')->value();
        $user = User::query()->where('email', $email)->first();

        if ($user && $user->active) {
            Password::sendResetLink(['email' => $email]);
        }

        return back()->with('success', 'If that email is registered, we sent a reset link.');
    }
}
