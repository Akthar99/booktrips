<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\DiscountType;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Package;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AnalyticsService
{
    /**
     * Partner analytics: summary, monthly income and per-package performance.
     *
     * @return array<string, mixed>
     */
    public function forBusiness(Business $business, ?string $from = null, ?string $to = null): array
    {
        $packages = $business->packages()->get();

        $bookings = Booking::query()
            ->where('business_id', $business->id)
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->get();

        $completed = $bookings->where('status', BookingStatus::Completed);
        $confirmedOrCompleted = $bookings->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed]);
        $decided = $bookings->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed, BookingStatus::Rejected]);

        $income = $this->sum($completed);
        $commission = $completed->sum(
            fn (Booking $booking): int => $booking->commission_lkr ?: (int) round($booking->total_lkr * (float) config('booktrips.commission_rate')),
        );

        return [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'city' => $business->city,
            ],
            'range' => [
                'from' => $from ?: null,
                'to' => $to ?: null,
            ],
            'summary' => [
                'packages' => $packages->count(),
                'live_packages' => $packages->where('active', true)->count(),
                'discounted_packages' => $packages->filter(
                    fn (Package $package): bool => $package->discount_type !== DiscountType::None && $package->discount_value > 0,
                )->count(),
                'bookings' => $bookings->count(),
                'requested' => $bookings->where('status', BookingStatus::Requested)->count(),
                'confirmed' => $confirmedOrCompleted->count(),
                'completed' => $completed->count(),
                'rejected' => $bookings->where('status', BookingStatus::Rejected)->count(),
                'guests' => (int) $bookings->sum('guests'),
                'income_lkr' => $income,
                'potential_income_lkr' => $this->sum($confirmedOrCompleted),
                'discounts_lkr' => (int) $completed->sum('discount_lkr'),
                'commission_lkr' => $commission,
                'net_income_lkr' => $income - $commission,
                'average_completed_lkr' => $completed->count() ? (int) round($income / $completed->count()) : 0,
                'confirmation_rate' => $decided->count()
                    ? (int) round(($confirmedOrCompleted->count() / $decided->count()) * 100)
                    : 0,
            ],
            'monthly' => $this->monthly($bookings),
            'packages' => $this->packageRows($packages, $bookings),
        ];
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     * @return array<int, array<string, mixed>>
     */
    private function monthly(Collection $bookings): array
    {
        $map = [];

        foreach ($bookings as $booking) {
            $key = Carbon::parse($booking->check_out ?? $booking->created_at)->format('Y-m');

            $map[$key] ??= [
                'period' => $key,
                'bookings' => 0,
                'completed' => 0,
                'income_lkr' => 0,
                'commission_lkr' => 0,
            ];

            $map[$key]['bookings']++;

            if ($booking->status === BookingStatus::Completed) {
                $map[$key]['completed']++;
                $map[$key]['income_lkr'] += $booking->total_lkr;
                $map[$key]['commission_lkr'] += $booking->commission_lkr
                    ?: (int) round($booking->total_lkr * (float) config('booktrips.commission_rate'));
            }
        }

        krsort($map);

        return array_slice(array_values($map), 0, 12);
    }

    /**
     * @param  Collection<int, Package>  $packages
     * @param  Collection<int, Booking>  $bookings
     * @return array<int, array<string, mixed>>
     */
    private function packageRows(Collection $packages, Collection $bookings): array
    {
        return $packages
            ->map(function (Package $package) use ($bookings): array {
                $rows = $bookings->where('package_id', $package->id);
                $done = $rows->where('status', BookingStatus::Completed);
                $accepted = $rows->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed]);

                return [
                    'id' => $package->id,
                    'title' => $package->title,
                    'category' => $package->category,
                    'active' => $package->active,
                    'price_lkr' => $package->price_lkr,
                    'discount_type' => $package->discount_type->value,
                    'discount_value' => $package->discount_value,
                    'bookings' => $rows->count(),
                    'requested' => $rows->where('status', BookingStatus::Requested)->count(),
                    'confirmed' => $accepted->count(),
                    'completed' => $done->count(),
                    'rejected' => $rows->where('status', BookingStatus::Rejected)->count(),
                    'cancelled' => $rows->where('status', BookingStatus::Cancelled)->count(),
                    'guests' => (int) $rows->sum('guests'),
                    'income_lkr' => $done->sum('total_lkr'),
                    'potential_income_lkr' => $accepted->sum('total_lkr'),
                    'discounts_lkr' => (int) $done->sum('discount_lkr'),
                    'confirmation_rate' => $rows->count()
                        ? (int) round(($accepted->count() / $rows->count()) * 100)
                        : 0,
                ];
            })
            ->sortBy([
                ['income_lkr', 'desc'],
                ['bookings', 'desc'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     */
    private function sum(Collection $bookings): int
    {
        return (int) $bookings->sum('total_lkr');
    }
}
