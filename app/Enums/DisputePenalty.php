<?php

namespace App\Enums;

enum DisputePenalty: string
{
    case None = 'none';
    case Warning = 'warning';
    case Strike = 'strike';
    case Suspend = 'suspend';

    public function label(): string
    {
        return match ($this) {
            self::None => 'No penalty',
            self::Warning => 'Formal warning',
            self::Strike => 'Strike',
            self::Suspend => 'Suspend account',
        };
    }
}
