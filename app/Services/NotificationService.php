<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Throwable;

class NotificationService
{
    /**
     * Send an in-app notification, never letting a failure break the flow.
     */
    public function notify(User $user, string $type, string $title, string $body = '', ?int $bookingId = null, ?string $link = null): void
    {
        try {
            $user->notify(new ActivityNotification([
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'booking_id' => $bookingId,
                'link' => $link,
            ]));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function notifyAdmins(string $type, string $title, string $body = '', ?int $bookingId = null, ?string $link = null): void
    {
        User::query()
            ->where('role', UserRole::Admin)
            ->where('active', true)
            ->each(fn (User $admin) => $this->notify($admin, $type, $title, $body, $bookingId, $link));
    }

    public function notifyBusinessOwner(Business $business, string $type, string $title, string $body = '', ?int $bookingId = null, ?string $link = null): void
    {
        $business->loadMissing('user');

        $owner = $business->user;

        if ($owner) {
            $this->notify($owner, $type, $title, $body, $bookingId, $link);
        }
    }
}
