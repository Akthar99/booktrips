<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationController extends Controller
{
    /**
     * Show the "check your inbox" page.
     */
    public function notice(Request $request): Response|RedirectResponse
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return to_route('home');
        }

        return Inertia::render('auth/verify-email', [
            'email' => $request->user()?->email,
            'pendingEmail' => $request->user()?->pending_email,
            'verified' => false,
        ]);
    }

    /**
     * Handle the signed verification link.
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->fulfill();
        }

        return to_route('home')->with('success', 'Email verified. You can book trips now.');
    }

    /**
     * Resend the verification email.
     */
    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return to_route('home')->with('success', 'Your email is already verified.');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Verification email sent.');
    }
}
