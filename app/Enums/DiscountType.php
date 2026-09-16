<?php

namespace App\Enums;

enum DiscountType: string
{
    case None = 'none';
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::None => 'No discount',
            self::Percentage => 'Percentage off',
            self::Fixed => 'Fixed amount off',
        };
    }
}
