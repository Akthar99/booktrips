<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/login', [
            'status' => session('status'),
        ]);
    }

    /**
     * Authenticate the user with throttling and a suspension check.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $key = Str::transliterate(Str::lower($request->string('email')).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Try again in {$seconds} seconds.",
            ]);
        }

        $credentials = $request->only('email', 'password');

        if (! Auth::validate($credentials)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => 'Incorrect email or password.',
            ]);
        }

        /** @var User $user */
        $user = User::query()->where('email', $credentials['email'])->firstOrFail();

        if ($user->isSuspended()) {
            throw ValidationException::withMessages([
                'email' => 'This account is suspended. Contact BookTrips.',
            ]);
        }

        RateLimiter::clear($key);

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        $fallback = $request->string('next')->value() ?: route('home');

        return redirect()->intended($fallback);
    }

    /**
     * Log the user out.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home');
    }
}
