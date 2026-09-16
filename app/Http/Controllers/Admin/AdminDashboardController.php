<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\ReceiptStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Receipt;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    /**
     * Operations overview: what needs attention across BookTrips.
     */
    public function index(): Response
    {
        return Inertia::render('admin/overview', [
            'stats' => [
                'users' => User::query()->count(),
                'travellers' => User::query()->where('role', UserRole::User)->count(),
                'partners' => Business::query()->count(),
                'partners_pending' => Business::query()->where('approved', false)->count(),
                'packages_live' => Package::query()->where('active', true)->count(),
                'bookings' => Booking::query()->count(),
                'bookings_requested' => Booking::query()->where('status', BookingStatus::Requested)->count(),
                'bookings_confirmed' => Booking::query()->where('status', BookingStatus::Confirmed)->count(),
                'bookings_completed' => Booking::query()->where('status', BookingStatus::Completed)->count(),
                'gmv_lkr' => (int) Booking::query()
                    ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
                    ->sum('total_lkr'),
                'commission_due_lkr' => (int) Invoice::query()
                    ->where('status', '!=', InvoiceStatus::Paid)
                    ->sum('amount_lkr'),
                'receipts_pending' => Receipt::query()->where('status', ReceiptStatus::Pending)->count(),
                'bookings_escalated' => Booking::query()
                    ->where('escalated', true)
                    ->where('status', BookingStatus::Requested)
                    ->count(),
            ],
        ]);
    }
}
