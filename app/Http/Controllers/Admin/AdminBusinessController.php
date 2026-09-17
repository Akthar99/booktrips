<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Dispute;
use App\Models\SupportTicket;
use App\Presenters\BookingPresenter;
use App\Services\CommissionService;
use App\Services\DisputeService;
use App\Services\SupportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Everything about one partner: who they are, what they sold and what went wrong.
 */
class AdminBusinessController extends Controller
{
    public function __construct(
        private readonly BookingPresenter $bookings,
        private readonly CommissionService $commission,
        private readonly DisputeService $disputes,
        private readonly SupportService $support,
    ) {}

    public function show(Request $request, Business $business): Response
    {
        $business->load('user');

        $bookings = $business->bookings()
            ->with(['package', 'user'])
            ->latest()
            ->take(25)
            ->get();

        return Inertia::render('admin/businesses/show', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'type' => $business->type->label(),
                'city' => $business->city,
                'district' => $business->district,
                'phone' => $business->phone,
                'email' => $business->email,
                'website' => $business->website,
                'instagram' => $business->instagram,
                'facebook' => $business->facebook,
                'tiktok' => $business->tiktok,
                'whatsapp' => $business->whatsapp,
                'approved' => $business->approved,
                'strikes' => $business->strikes,
                'phone_verified_at' => $business->phone_verified_at?->toISOString(),
                'created_at' => $business->created_at?->toISOString(),
                'owner' => $business->user ? [
                    'id' => $business->user->id,
                    'name' => $business->user->name,
                    'email' => $business->user->email,
                    'active' => $business->user->active,
                    'email_verified' => $business->user->hasVerifiedEmail(),
                ] : null,
                'packages_total' => $business->packages()->count(),
                'packages_live' => $business->packages()->where('active', true)->count(),
            ],
            'totals' => [
                'bookings' => $business->bookings()->count(),
                'completed' => $business->bookings()->where('status', BookingStatus::Completed)->count(),
                'gmv_lkr' => (int) $business->bookings()
                    ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
                    ->sum('total_lkr'),
                'commission_lkr' => (int) $business->invoices()->sum('amount_lkr'),
                'paid_lkr' => (int) $business->invoices()->where('status', 'paid')->sum('amount_lkr'),
            ],
            'bookings' => $bookings
                ->map(fn ($booking): array => [
                    ...$this->bookings->forAdmin($booking),
                    'created_at' => $booking->created_at?->toISOString(),
                ])
                ->all(),
            'invoices' => $business->invoices()
                ->orderByDesc('period')
                ->get()
                ->map(fn ($invoice): array => [
                    'id' => $invoice->id,
                    'period' => $invoice->period,
                    'period_label' => $this->commission->monthLabel($invoice->period),
                    'amount_lkr' => $invoice->amount_lkr,
                    'status' => $invoice->status->value,
                    'paid_at' => $invoice->paid_at?->toISOString(),
                    'due_date' => $this->commission->lastDayOfPeriod($invoice->period),
                    'lines' => $invoice->lines ?? [],
                ])
                ->all(),
            'disputes' => Dispute::query()
                ->where('business_id', $business->id)
                ->with(['raisedBy', 'against', 'booking'])
                ->latest()
                ->take(20)
                ->get()
                ->map(fn (Dispute $dispute): array => [
                    ...$this->disputes->summary($dispute),
                    'booking_code' => $dispute->booking?->booking_code,
                    'booking_id' => $dispute->booking_id,
                    'against' => $dispute->against?->name,
                ])
                ->all(),
            'tickets' => SupportTicket::query()
                ->where('user_id', $business->user_id)
                ->with('user')
                ->latest('last_message_at')
                ->take(10)
                ->get()
                ->map(fn (SupportTicket $ticket): array => $this->support->summary($ticket, false))
                ->all(),
        ]);
    }
}
