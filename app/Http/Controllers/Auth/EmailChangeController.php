<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\RequestEmailChangeRequest;
use App\Models\User;
use App\Notifications\EmailChangeNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class EmailChangeController extends Controller
{
    /**
     * Store the requested new email and mail a confirmation link to it.
     */
    public function store(RequestEmailChangeRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->forceFill([
            'pending_email' => $request->string('email')->value(),
            'pending_email_requested_at' => now(),
        ])->save();

        $url = URL::temporarySignedRoute(
            'email.change.confirm',
            now()->addHours(24),
            ['user' => $user->id],
        );

        $user->notify(new EmailChangeNotification($url));

        return back()->with('success', 'We sent a confirmation link to the new address.');
    }

    /**
     * Confirm the pending email change from the signed link.
     */
    public function confirm(Request $request, User $user): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'This confirmation link is invalid or has expired.');
        }

        $pending = $user->pending_email;

        if (! $pending) {
            return to_route('account.index')->with('error', 'There is no pending email change for this account.');
        }

        if (User::query()->where('email', $pending)->exists()) {
            $user->forceFill([
                'pending_email' => null,
                'pending_email_requested_at' => null,
            ])->save();

            return to_route('account.index')->with('error', 'That email was taken in the meantime. Please try another.');
        }

        $user->forceFill([
            'email' => $pending,
            'email_verified_at' => now(),
            'pending_email' => null,
            'pending_email_requested_at' => null,
        ])->save();

        return to_route('account.index')->with('success', 'Email updated and verified.');
    }
}
