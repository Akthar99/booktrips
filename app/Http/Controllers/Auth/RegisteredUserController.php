<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the traveller sign-up page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    /**
     * Create a traveller account, sign them in and start email verification.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = DB::transaction(fn (): User => User::create([
            'name' => $request->string('name')->value(),
            'email' => $request->string('email')->value(),
            'phone' => $request->filled('phone') ? $request->string('phone')->value() : null,
            'password' => $request->string('password')->value(),
        ]));

        $user->forceFill(['role' => UserRole::User])->save();

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        return to_route('verification.notice')
            ->with('success', 'Account created. Check your inbox for the verification link.');
    }
}
