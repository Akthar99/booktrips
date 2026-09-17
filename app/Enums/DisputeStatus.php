<?php

namespace App\Enums;

enum DisputeStatus: string
{
    case AwaitingResponse = 'awaiting_response';
    case UnderReview = 'under_review';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingResponse => 'Waiting for a response',
            self::UnderReview => 'With BookTrips',
            self::Resolved => 'Resolved',
        };
    }

    public function isOpen(): bool
    {
        return $this !== self::Resolved;
    }
}
