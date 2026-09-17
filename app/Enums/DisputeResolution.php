<?php

namespace App\Enums;

enum DisputeResolution: string
{
    case CustomerFault = 'customer_fault';
    case PartnerFault = 'partner_fault';
    case NoFault = 'no_fault';

    public function label(): string
    {
        return match ($this) {
            self::CustomerFault => 'Traveller at fault',
            self::PartnerFault => 'Partner at fault',
            self::NoFault => 'Nobody at fault',
        };
    }
}
