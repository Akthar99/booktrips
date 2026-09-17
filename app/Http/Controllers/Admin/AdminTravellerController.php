<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\SupportTicket;
use App\Models\User;
use App\Presenters\BookingPresenter;
use App\Services\DisputeService;
use App\Services\SupportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Everything about one traveller: their trips, reports and requests.
 */
class AdminTravellerController extends Controller
{
    public function __construct(
        private readonly BookingPresenter $bookings,
        private readonly DisputeService $disputes,
        private readonly SupportService $support,
    ) {}

    public function show(Request $request, User $user): Response
    {
        $bookings = $user->bookings()
            ->with(['package.business', 'user'])
            ->latest()
            ->take(25)
            ->get();

        return Inertia::render('admin/travellers/show', [
            'traveller' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role->value,
                'active' => $user->active,
                'strikes' => $user->strikes,
                'email_verified' => $user->hasVerifiedEmail(),
                'pending_email' => $user->pending_email,
                'created_at' => $user->created_at?->toISOString(),
            ],
            'totals' => [
                'bookings' => $user->bookings()->count(),
                'completed' => $user->bookings()->where('status', BookingStatus::Completed)->count(),
                'cancelled' => $user->bookings()->where('status', BookingStatus::Cancelled)->count(),
                'spend_lkr' => (int) $user->bookings()
                    ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
                    ->sum('total_lkr'),
                'reviews' => $user->reviews()->count(),
            ],
            'bookings' => $bookings
                ->map(fn ($booking): array => [
                    ...$this->bookings->forAdmin($booking),
                    'created_at' => $booking->created_at?->toISOString(),
                ])
                ->all(),
            'disputes' => Dispute::query()
                ->where('against_user_id', $user->id)
                ->orWhere('raised_by_user_id', $user->id)
                ->with(['business', 'booking', 'raisedBy', 'against'])
                ->latest()
                ->take(20)
                ->get()
                ->map(fn (Dispute $dispute): array => [
                    ...$this->disputes->summary($dispute),
                    'booking_code' => $dispute->booking?->booking_code,
                    'booking_id' => $dispute->booking_id,
                    'raised_by_me' => $dispute->raised_by_user_id === $user->id,
                    'business_name' => $dispute->business?->name,
                ])
                ->all(),
            'tickets' => SupportTicket::query()
                ->where('user_id', $user->id)
                ->with('user')
                ->latest('last_message_at')
                ->take(10)
                ->get()
                ->map(fn (SupportTicket $ticket): array => $this->support->summary($ticket, false))
                ->all(),
        ]);
    }
}
