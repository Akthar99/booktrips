<?php

namespace App\Http\Controllers\Partner;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DisputeController;
use App\Http\Requests\Partner\BookingStatusRequest;
use App\Models\Booking;
use App\Models\Dispute;
use App\Presenters\BookingPresenter;
use App\Services\BookingService;
use App\Services\DisputeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartnerBookingController extends Controller
{
    public function __construct(
        private readonly BookingPresenter $presenter,
        private readonly BookingService $bookings,
    ) {}

    /**
     * Reservations for the partner's packages, with guest contact redacted until confirmed.
     */
    public function index(Request $request): Response
    {
        $business = $request->user()->business;
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $date = (string) $request->query('date', '');

        $bookings = Booking::query()
            ->where('business_id', $business->id)
            ->with(['package.business', 'user'])
            ->when($status !== '' && $status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($date !== '', fn ($query) => $query->whereDate('check_in', $date))
            ->when($q !== '', function ($query) use ($q): void {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like): void {
                    $inner->where('booking_code', 'like', $like)
                        ->orWhere('guest_name', 'like', $like)
                        ->orWhere('guest_phone', 'like', $like)
                        ->orWhereHas('package', fn ($package) => $package->where('title', 'like', $like));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Booking $booking): array => $this->presenter->forPartner($booking));

        return Inertia::render('partner/bookings/index', [
            'bookings' => $bookings,
            'filters' => ['q' => $q, 'status' => $status, 'date' => $date],
            'counts' => [
                'requested' => Booking::query()->where('business_id', $business->id)->where('status', BookingStatus::Requested)->count(),
                'confirmed' => Booking::query()->where('business_id', $business->id)->where('status', BookingStatus::Confirmed)->count(),
            ],
        ]);
    }

    /**
     * A single reservation with contact details when the booking is confirmed.
     */
    public function show(Request $request, Booking $booking): Response
    {
        $this->authorize('manageAsPartner', $booking);

        $booking->load(['package.business', 'user']);

        $disputes = app(DisputeService::class);
        $viewer = $request->user();

        return Inertia::render('partner/bookings/show', [
            'booking' => $this->presenter->forPartner($booking),
            'disputes' => $booking->disputes()->latest()->get()
                ->map(fn (Dispute $dispute): array => $disputes->forParty($dispute, $viewer))
                ->all(),
            'reportTypes' => DisputeController::typesFor(true),
            'canReport' => $disputes->canReport($viewer, $booking),
        ]);
    }

    /**
     * Confirm, reject, complete or cancel a reservation.
     */
    public function updateStatus(BookingStatusRequest $request, Booking $booking): RedirectResponse
    {
        $this->authorize('manageAsPartner', $booking);

        $this->bookings->updateStatus(
            $booking,
            BookingStatus::from($request->string('status')->value()),
            $request->user(),
        );

        return back()->with('success', 'Booking updated.');
    }
}
