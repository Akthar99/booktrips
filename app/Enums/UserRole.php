<?php

namespace App\Enums;

enum UserRole: string
{
    case User = 'user';
    case Business = 'business';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::User => 'Traveller',
            self::Business => 'Partner',
            self::Admin => 'Super admin',
        };
    }
}
