<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Enums\PriceType;
use App\Models\Package;
use Illuminate\Support\Carbon;

class PricingService
{
    /**
     * Whether the package discount applies on the given date.
     */
    public function discountIsActive(Package $package, string|Carbon|null $date = null): bool
    {
        $type = $package->discount_type ?? DiscountType::None;
        $value = (float) $package->discount_value;

        if (! $package->discount_enabled || ! in_array($type, [DiscountType::Percentage, DiscountType::Fixed], true) || $value <= 0) {
            return false;
        }

        $day = $this->isoDate($date);

        if ($package->discount_start && $day < $package->discount_start->toDateString()) {
            return false;
        }

        if ($package->discount_end && $day > $package->discount_end->toDateString()) {
            return false;
        }

        return true;
    }

    /**
     * Package-level pricing for one unit, honouring any active discount.
     *
     * @return array<string, mixed>
     */
    public function pricingFor(Package $package, string|Carbon|null $date = null): array
    {
        $basePrice = max($package->price_lkr, 0);
        $active = $this->discountIsActive($package, $date);
        $type = $package->discount_type ?? DiscountType::None;
        $value = (float) $package->discount_value;
        $discountAmount = 0;

        if ($active) {
            $discountAmount = $type === DiscountType::Percentage
                ? (int) round($basePrice * min($value, 100) / 100)
                : (int) min(round($value), $basePrice);
        }

        $finalPrice = max($basePrice - $discountAmount, 0);

        return [
            'base_price_lkr' => $basePrice,
            'display_price_lkr' => $finalPrice,
            'discount_lkr' => $discountAmount,
            'discount_active' => $active && $discountAmount > 0,
            'discount_type' => $type->value,
            'discount_value' => $value,
            'discount_label' => $this->discountLabel($active, $discountAmount, $type, $value),
        ];
    }

    /**
     * Full booking total for a stay.
     *
     * @return array<string, mixed>
     */
    public function calculateTotal(Package $package, int $guests, string $checkIn, string $checkOut): array
    {
        $nights = $this->nightsBetween($checkIn, $checkOut);
        $units = $package->duration_days <= 1 && ! $package->duration_nights
            ? 1
            : max($nights, $package->duration_nights ?: 1);

        $multiplier = ($package->price_type === PriceType::PerPerson ? $guests : 1)
            * ($package->price_type === PriceType::PerNight ? $units : 1);

        $pricing = $this->pricingFor($package, $checkIn);

        return [
            'nights' => $nights,
            'units' => $units,
            'guests' => $guests,
            'base_total_lkr' => $pricing['base_price_lkr'] * $multiplier,
            'discount_lkr' => $pricing['discount_lkr'] * $multiplier,
            'total_lkr' => $pricing['display_price_lkr'] * $multiplier,
            'discount_active' => $pricing['discount_active'],
            'discount_type' => $pricing['discount_type'],
            'discount_value' => $pricing['discount_value'],
            'discount_label' => $pricing['discount_label'],
        ];
    }

    public function nightsBetween(string $checkIn, string $checkOut): int
    {
        $nights = (int) round(
            Carbon::parse($checkIn)->startOfDay()->diffInDays(Carbon::parse($checkOut)->startOfDay(), false)
        );

        return max($nights, 0);
    }

    private function discountLabel(bool $active, int $discountAmount, DiscountType $type, float $value): string
    {
        if (! $active || $discountAmount <= 0) {
            return '';
        }

        return $type === DiscountType::Percentage
            ? number_format(min($value, 100), 0).'% off'
            : 'Rs. '.number_format($discountAmount).' off';
    }

    private function isoDate(string|Carbon|null $date): string
    {
        if ($date instanceof Carbon) {
            return $date->toDateString();
        }

        if (is_string($date) && $date !== '') {
            return substr($date, 0, 10);
        }

        return Carbon::now()->toDateString();
    }
}
