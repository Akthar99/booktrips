<?php

namespace App\Enums;

enum DisputeType: string
{
    case NoShow = 'no_show';
    case PaymentDenied = 'payment_denied';
    case ServiceNotDelivered = 'service_not_delivered';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::NoShow => 'Guest did not turn up',
            self::PaymentDenied => 'Host says payment was not made',
            self::ServiceNotDelivered => 'Service was not delivered',
            self::Other => 'Something else',
        };
    }

    public function blurb(): string
    {
        return match ($this) {
            self::NoShow => 'The booking was confirmed and the guest never arrived or informed you.',
            self::PaymentDenied => 'You attended and paid, but the host says the payment was not made.',
            self::ServiceNotDelivered => 'You arrived but the package was not delivered as listed.',
            self::Other => 'Anything else you need BookTrips to look at.',
        };
    }

    /**
     * Types a business owner may raise.
     *
     * @return array<int, self>
     */
    public static function byPartner(): array
    {
        return [self::NoShow, self::Other];
    }

    /**
     * Types a traveller may raise.
     *
     * @return array<int, self>
     */
    public static function byTraveller(): array
    {
        return [self::PaymentDenied, self::ServiceNotDelivered, self::Other];
    }
}
