<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case AwaitingAdmin = 'awaiting_admin';
    case AwaitingUser = 'awaiting_user';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::AwaitingAdmin => 'Waiting for BookTrips',
            self::AwaitingUser => 'Waiting for you',
            self::Resolved => 'Resolved',
        };
    }
}
