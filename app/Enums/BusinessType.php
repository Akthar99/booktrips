<?php

namespace App\Enums;

enum BusinessType: string
{
    case Hotel = 'hotel';
    case Villa = 'villa';
    case TourOperator = 'tour_operator';
    case ActivityProvider = 'activity_provider';
    case CampingSite = 'camping_site';
    case Guesthouse = 'guesthouse';

    public function label(): string
    {
        return match ($this) {
            self::Hotel => 'Hotel',
            self::Villa => 'Villa / holiday home',
            self::TourOperator => 'Tour operator',
            self::ActivityProvider => 'Activity provider',
            self::CampingSite => 'Camping site',
            self::Guesthouse => 'Guesthouse / boutique stay',
        };
    }
}
