<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Requested = 'requested';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Requested',
            self::Confirmed => 'Confirmed',
            self::Rejected => 'Rejected',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Statuses that still hold capacity on a package for their dates.
     *
     * @return array<int, self>
     */
    public static function holdingCapacity(): array
    {
        return [self::Requested, self::Confirmed, self::Completed];
    }

    /**
     * Statuses a guest may cancel from.
     *
     * @return array<int, self>
     */
    public static function guestCancellable(): array
    {
        return [self::Requested, self::Confirmed];
    }

    /**
     * Statuses a partner may set from the dashboard.
     *
     * @return array<int, self>
     */
    public static function partnerSettable(): array
    {
        return [self::Confirmed, self::Rejected, self::Completed, self::Cancelled];
    }

    public function holdsCapacity(): bool
    {
        return in_array($this, self::holdingCapacity(), true);
    }

    public function exposesGuestContact(): bool
    {
        return $this === self::Confirmed || $this === self::Completed;
    }
}
