<?php

namespace App\Enums;

enum TicketCategory: string
{
    case Booking = 'booking';
    case Payment = 'payment';
    case Account = 'account';
    case Partner = 'partner';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Booking => 'A booking or trip',
            self::Payment => 'Payments or commission',
            self::Account => 'My account',
            self::Partner => 'Partner tools',
            self::Other => 'Something else',
        };
    }
}
