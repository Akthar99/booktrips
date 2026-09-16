<?php

namespace App\Enums;

enum PriceType: string
{
    case PerPackage = 'per_package';
    case PerPerson = 'per_person';
    case PerNight = 'per_night';

    public function label(): string
    {
        return match ($this) {
            self::PerPackage => 'per package',
            self::PerPerson => 'per person',
            self::PerNight => 'per night',
        };
    }
}
