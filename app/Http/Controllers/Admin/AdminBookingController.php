<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookingStatusRequest;
use App\Models\Booking;
use App\Presenters\BookingPresenter;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminBookingController extends Controller
{
    public function __construct(
        private readonly BookingPresenter $presenter,
        private readonly BookingService $bookings,
    ) {}

    /**
     * Every booking with the business that owns it.
     */
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $date = (string) $request->query('date', '');

        $bookings = Booking::query()
            ->with(['package.business', 'user'])
            ->when($status !== '' && $status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($date !== '', fn ($query) => $query->whereDate('check_in', $date))
            ->when($q !== '', function ($query) use ($q): void {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like): void {
                    $inner->where('booking_code', 'like', $like)
                        ->orWhere('guest_name', 'like', $like)
                        ->orWhere('guest_phone', 'like', $like)
                        ->orWhereHas('package', fn ($package) => $package->where('title', 'like', $like))
                        ->orWhereHas('business', fn ($business) => $business->where('name', 'like', $like));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Booking $booking): array => $this->presenter->forAdmin($booking));

        return Inertia::render('admin/bookings', [
            'bookings' => $bookings,
            'filters' => ['q' => $q, 'status' => $status, 'date' => $date],
            'counts' => [
                'requested' => Booking::query()->where('status', BookingStatus::Requested)->count(),
                'confirmed' => Booking::query()->where('status', BookingStatus::Confirmed)->count(),
            ],
        ]);
    }

    /**
     * Move any booking to any status.
     */
    public function updateStatus(BookingStatusRequest $request, Booking $booking): RedirectResponse
    {
        $this->bookings->updateStatus(
            $booking,
            BookingStatus::from($request->string('status')->value()),
            $request->user(),
        );

        return back()->with('success', 'Booking updated.');
    }
}
