<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Open = 'open';
    case Submitted = 'submitted';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Submitted => 'Submitted',
            self::Paid => 'Paid',
        };
    }
}
