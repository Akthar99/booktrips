<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\ReceiptStatus;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Dispute;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Receipt;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    /**
     * Operations overview: what needs attention across BookTrips.
     */
    public function index(): Response
    {
        $monthStart = now()->startOfMonth();

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
            'finance' => [
                'billed_lkr' => (int) Invoice::query()->sum('amount_lkr'),
                'collected_lkr' => (int) Invoice::query()->where('status', InvoiceStatus::Paid)->sum('amount_lkr'),
                'outstanding_lkr' => (int) Invoice::query()->where('status', '!=', InvoiceStatus::Paid)->sum('amount_lkr'),
                'overdue_lkr' => (int) Invoice::query()
                    ->where('status', '!=', InvoiceStatus::Paid)
                    ->where('created_at', '<', $monthStart)
                    ->sum('amount_lkr'),
                'overdue_invoices' => (int) Invoice::query()
                    ->where('status', '!=', InvoiceStatus::Paid)
                    ->where('created_at', '<', $monthStart)
                    ->count(),
                'paid_invoices' => (int) Invoice::query()->where('status', InvoiceStatus::Paid)->count(),
            ],
            'thisMonth' => [
                'label' => now()->format('F Y'),
                'bookings' => Booking::query()->where('created_at', '>=', $monthStart)->count(),
                'gmv_lkr' => (int) Booking::query()
                    ->where('created_at', '>=', $monthStart)
                    ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
                    ->sum('total_lkr'),
                'commission_lkr' => (int) Invoice::query()->where('created_at', '>=', $monthStart)->sum('amount_lkr'),
                'new_users' => User::query()->where('created_at', '>=', $monthStart)->count(),
                'new_partners' => Business::query()->where('created_at', '>=', $monthStart)->count(),
            ],
            'attention' => [
                'disputes_ready' => Dispute::query()->open()->whereNotNull('responded_at')->count(),
                'disputes_awaiting' => Dispute::query()->open()->whereNull('responded_at')->count(),
                'tickets_waiting' => SupportTicket::query()->where('status', TicketStatus::AwaitingAdmin)->count(),
                'receipts_pending' => Receipt::query()->where('status', ReceiptStatus::Pending)->count(),
                'partners_pending' => Business::query()->where('approved', false)->count(),
                'escalated' => Booking::query()->where('escalated', true)->where('status', BookingStatus::Requested)->count(),
            ],
            'months' => $this->monthly(),
            'topPartners' => $this->topPartners(),
        ]);
    }

    /**
     * Six months of bookings, GMV and commission.
     *
     * @return array<int, array<string, mixed>>
     */
    private function monthly(): array
    {
        $months = [];

        for ($offset = 5; $offset >= 0; $offset--) {
            $start = now()->subMonths($offset)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            $bookings = Booking::query()->whereBetween('created_at', [$start, $end]);

            $months[] = [
                'label' => $start->format('M'),
                'bookings' => (clone $bookings)->count(),
                'gmv_lkr' => (int) (clone $bookings)
                    ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
                    ->sum('total_lkr'),
                'commission_lkr' => (int) Invoice::query()->whereBetween('created_at', [$start, $end])->sum('amount_lkr'),
            ];
        }

        return $months;
    }

    /**
     * Partners ranked by completed business.
     *
     * @return array<int, array<string, mixed>>
     */
    private function topPartners(): array
    {
        $rows = DB::table('bookings')
            ->join('businesses', 'businesses.id', '=', 'bookings.business_id')
            ->where('bookings.status', BookingStatus::Completed->value)
            ->groupBy('businesses.id', 'businesses.name', 'businesses.city', 'businesses.strikes')
            ->selectRaw('businesses.id as id, businesses.name as name, businesses.city as city, businesses.strikes as strikes, count(*) as completed_count, sum(bookings.total_lkr) as completed_gmv')
            ->orderByDesc('completed_gmv')
            ->limit(5)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $invoiced = DB::table('invoices')
            ->whereIn('business_id', $rows->pluck('id'))
            ->groupBy('business_id')
            ->selectRaw('business_id, sum(amount_lkr) as total')
            ->pluck('total', 'business_id');

        return $rows
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'city' => (string) $row->city,
                'completed_count' => (int) $row->completed_count,
                'completed_gmv_lkr' => (int) $row->completed_gmv,
                'invoiced_lkr' => (int) ($invoiced[$row->id] ?? 0),
                'strikes' => (int) $row->strikes,
            ])
            ->values()
            ->all();
    }
}
