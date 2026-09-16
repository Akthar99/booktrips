<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Determine whether the user can view the booking.
     */
    public function view(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id;
    }

    /**
     * Determine whether the traveller can cancel the booking.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id;
    }

    /**
     * Determine whether the partner who owns the package can manage the booking.
     */
    public function manageAsPartner(User $user, Booking $booking): bool
    {
        return $user->business?->id === $booking->business_id;
    }
}
