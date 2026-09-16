<?php

namespace App\Http\Controllers;

use App\Http\Requests\Booking\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Package;
use App\Presenters\BookingPresenter;
use App\Presenters\CatalogPresenter;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly BookingPresenter $presenter,
        private readonly CatalogPresenter $catalog,
    ) {}

    /**
     * The traveller's bookings list.
     */
    public function index(Request $request): Response
    {
        $bookings = Booking::query()
            ->where('user_id', $request->user()->id)
            ->with(['user', 'package.business'])
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Booking $booking): array => $this->presenter->forGuest($booking));

        return Inertia::render('bookings/index', [
            'bookings' => $bookings,
        ]);
    }

    /**
     * The booking request page for a package.
     */
    public function create(Package $package): Response
    {
        if (! $package->active) {
            abort(404);
        }

        $package->load('business');

        return Inertia::render('bookings/create', [
            'package' => $this->catalog->packageDetail($package),
        ]);
    }

    /**
     * A single booking for its owner (admins can open any booking).
     */
    public function show(Booking $booking): Response
    {
        $this->authorize('view', $booking);

        $booking->load(['package.business', 'user', 'review']);

        return Inertia::render('bookings/show', [
            'booking' => $this->presenter->forGuest($booking, $booking->review),
        ]);
    }

    /**
     * Create a booking request; money is always recomputed server-side.
     */
    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $package = Package::query()->findOrFail($request->integer('package_id'));

        $booking = $this->bookings->create($request->user(), $package, [
            'check_in' => $request->string('check_in')->value(),
            'check_out' => $request->string('check_out')->value(),
            'guests' => $request->integer('guests'),
            'guest_name' => $request->string('guest_name')->value(),
            'guest_phone' => $request->string('guest_phone')->value(),
            'notes' => $request->input('notes'),
        ]);

        return to_route('bookings.show', $booking)
            ->with('success', 'Request sent. The host will confirm or decline. Pay at the destination if they accept.');
    }

    /**
     * Cancel one of your own bookings.
     */
    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('cancel', $booking);

        $this->bookings->cancelByGuest($booking, $request->user());

        return back()->with('success', 'Booking cancelled.');
    }
}
