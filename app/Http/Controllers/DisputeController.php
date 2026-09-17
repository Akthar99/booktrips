<?php

namespace App\Http\Controllers;

use App\Enums\DisputeType;
use App\Http\Requests\Dispute\OpenDisputeRequest;
use App\Http\Requests\Dispute\RespondToDisputeRequest;
use App\Models\Booking;
use App\Models\Dispute;
use App\Services\DisputeService;
use Illuminate\Http\RedirectResponse;

/**
 * Reporting a problem and answering a report, for both sides of a booking.
 */
class DisputeController extends Controller
{
    public function __construct(private readonly DisputeService $disputes) {}

    public function store(OpenDisputeRequest $request, Booking $booking): RedirectResponse
    {
        $this->authorize('report', [Dispute::class, $booking]);

        $this->disputes->open($request->user(), $booking, [
            'type' => $request->string('type')->value(),
            'summary' => $request->string('summary')->value(),
            'details' => $request->input('details'),
        ]);

        $back = $request->user()->isPartner()
            ? to_route('partner.bookings.show', $booking)
            : to_route('bookings.show', $booking);

        return $back->with('success', 'Report sent. The other side has 48 hours to respond before our team reviews it.');
    }

    public function respond(RespondToDisputeRequest $request, Dispute $dispute): RedirectResponse
    {
        $this->authorize('respond', $dispute);

        $this->disputes->respond($dispute, $request->user(), $request->string('response')->value());

        $back = $request->user()->isPartner()
            ? to_route('partner.bookings.show', $dispute->booking_id)
            : to_route('bookings.show', $dispute->booking_id);

        return $back->with('success', 'Thanks — your side is with our team now.');
    }

    /**
     * The report types available to the signed-in side (used by the UI).
     *
     * @return array<int, array<string, string>>
     */
    public static function typesFor(bool $isPartner): array
    {
        return array_map(
            fn (DisputeType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
                'blurb' => $type->blurb(),
            ],
            $isPartner ? DisputeType::byPartner() : DisputeType::byTraveller(),
        );
    }
}
