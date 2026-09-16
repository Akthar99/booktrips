<?php

namespace App\Http\Controllers\Partner;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Package;
use App\Presenters\CatalogPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartnerDashboardController extends Controller
{
    public function __construct(private readonly CatalogPresenter $presenter) {}

    /**
     * Partner extranet overview: stats plus their packages.
     */
    public function index(Request $request): Response
    {
        $business = $request->user()->business;
        $packages = $business->packages()->orderByDesc('created_at')->get();

        $bookings = Booking::query()->where('business_id', $business->id);

        return Inertia::render('partner/dashboard', [
            'stats' => [
                'packages' => $packages->count(),
                'bookings' => (clone $bookings)->count(),
                'upcoming' => (clone $bookings)
                    ->where('status', BookingStatus::Confirmed)
                    ->whereDate('check_in', '>=', now()->toDateString())
                    ->count(),
                'requested' => (clone $bookings)->where('status', BookingStatus::Requested)->count(),
            ],
            'packages' => $packages
                ->map(fn (Package $package): array => $this->presenter->packageCard($package))
                ->all(),
        ]);
    }
}
