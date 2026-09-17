<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\Dispute;
use App\Models\User;

class DisputePolicy
{
    public function view(User $user, Dispute $dispute): bool
    {
        return $user->isAdmin()
            || $dispute->raised_by_user_id === $user->id
            || $dispute->against_user_id === $user->id;
    }

    /**
     * Only the two sides of the booking may report each other.
     */
    public function report(User $user, Booking $booking): bool
    {
        if ($user->isAdmin()) {
            return false;
        }

        return $booking->user_id === $user->id
            || ($user->business && $booking->business_id === $user->business->id);
    }

    /**
     * Only the accused party responds to a report.
     */
    public function respond(User $user, Dispute $dispute): bool
    {
        return $dispute->isOpen() && $dispute->against_user_id === $user->id;
    }

    /**
     * Only super admins hand down verdicts.
     */
    public function resolve(User $user, Dispute $dispute): bool
    {
        return $user->isAdmin() && $dispute->canBeResolved();
    }
}
