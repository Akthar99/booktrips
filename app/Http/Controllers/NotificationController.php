<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Mark one of the signed-in user's notifications as read.
     */
    public function read(Request $request, string $notification): RedirectResponse
    {
        $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail()
            ->markAsRead();

        return back();
    }

    /**
     * Mark every notification as read.
     */
    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
