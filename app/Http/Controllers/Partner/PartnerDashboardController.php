<?php

namespace App\Http\Controllers\Partner;

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
        $packages = $business->packages()->with('business')->orderByDesc('created_at')->get();

        // One aggregate instead of a count query per card.
        $stats = Booking::query()
            ->where('business_id', $business->id)
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when status = 'confirmed' and check_in >= ? then 1 else 0 end) as upcoming", [now()->toDateString()])
            ->selectRaw("sum(case when status = 'requested' then 1 else 0 end) as requested")
            ->first();

        return Inertia::render('partner/dashboard', [
            'stats' => [
                'packages' => $packages->count(),
                'bookings' => (int) ($stats->total ?? 0),
                'upcoming' => (int) ($stats->upcoming ?? 0),
                'requested' => (int) ($stats->requested ?? 0),
            ],
            'packages' => $packages
                ->map(fn (Package $package): array => $this->presenter->packageCard($package))
                ->all(),
        ]);
    }
}
