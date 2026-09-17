<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\DisputeStatus;
use App\Enums\InvoiceStatus;
use App\Enums\ReceiptStatus;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Receipt;
use App\Models\SupportTicket;
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

        // One aggregate per table. This page used to fire ~50 separate count/sum
        // queries, which is slow on any hosting and brutal over a remote database.
        $requested = BookingStatus::Requested->value;
        $confirmed = BookingStatus::Confirmed->value;
        $completed = BookingStatus::Completed->value;
        $paid = InvoiceStatus::Paid->value;

        $users = (array) DB::table('users')
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when role = ? then 1 else 0 end) as travellers', [UserRole::User->value])
            ->selectRaw('sum(case when created_at >= ? then 1 else 0 end) as new_this_month', [$monthStart])
            ->first();

        $businesses = (array) DB::table('businesses')
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when approved = 0 then 1 else 0 end) as pending')
            ->selectRaw('sum(case when created_at >= ? then 1 else 0 end) as new_this_month', [$monthStart])
            ->first();

        $bookings = (array) DB::table('bookings')
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as requested', [$requested])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as confirmed', [$confirmed])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as completed', [$completed])
            ->selectRaw('sum(case when status in (?, ?) then total_lkr else 0 end) as gmv', [$confirmed, $completed])
            ->selectRaw('sum(case when escalated = 1 and status = ? then 1 else 0 end) as escalated', [$requested])
            ->selectRaw('sum(case when created_at >= ? then 1 else 0 end) as this_month', [$monthStart])
            ->selectRaw('sum(case when created_at >= ? and status in (?, ?) then total_lkr else 0 end) as gmv_this_month', [$monthStart, $confirmed, $completed])
            ->first();

        $invoices = (array) DB::table('invoices')
            ->selectRaw('sum(amount_lkr) as billed')
            ->selectRaw('sum(case when status = ? then amount_lkr else 0 end) as collected', [$paid])
            ->selectRaw('sum(case when status != ? then amount_lkr else 0 end) as outstanding', [$paid])
            ->selectRaw('sum(case when status != ? and created_at < ? then amount_lkr else 0 end) as overdue', [$paid, $monthStart])
            ->selectRaw('sum(case when status != ? and created_at < ? then 1 else 0 end) as overdue_invoices', [$paid, $monthStart])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as paid_invoices', [$paid])
            ->selectRaw('sum(case when created_at >= ? then amount_lkr else 0 end) as commission_this_month', [$monthStart])
            ->first();

        $disputes = (array) DB::table('disputes')
            ->where('status', '!=', DisputeStatus::Resolved->value)
            ->selectRaw('sum(case when responded_at is not null then 1 else 0 end) as ready')
            ->selectRaw('sum(case when responded_at is null then 1 else 0 end) as awaiting')
            ->first();

        $packagesLive = Package::query()->where('active', true)->count();
        $receiptsPending = Receipt::query()->where('status', ReceiptStatus::Pending)->count();
        $ticketsWaiting = SupportTicket::query()->where('status', TicketStatus::AwaitingAdmin)->count();

        return Inertia::render('admin/overview', [
            'stats' => [
                'users' => (int) ($users['total'] ?? 0),
                'travellers' => (int) ($users['travellers'] ?? 0),
                'partners' => (int) ($businesses['total'] ?? 0),
                'partners_pending' => (int) ($businesses['pending'] ?? 0),
                'packages_live' => $packagesLive,
                'bookings' => (int) ($bookings['total'] ?? 0),
                'bookings_requested' => (int) ($bookings['requested'] ?? 0),
                'bookings_confirmed' => (int) ($bookings['confirmed'] ?? 0),
                'bookings_completed' => (int) ($bookings['completed'] ?? 0),
                'gmv_lkr' => (int) ($bookings['gmv'] ?? 0),
                'commission_due_lkr' => (int) ($invoices['outstanding'] ?? 0),
                'receipts_pending' => $receiptsPending,
                'bookings_escalated' => (int) ($bookings['escalated'] ?? 0),
            ],
            'finance' => [
                'billed_lkr' => (int) ($invoices['billed'] ?? 0),
                'collected_lkr' => (int) ($invoices['collected'] ?? 0),
                'outstanding_lkr' => (int) ($invoices['outstanding'] ?? 0),
                'overdue_lkr' => (int) ($invoices['overdue'] ?? 0),
                'overdue_invoices' => (int) ($invoices['overdue_invoices'] ?? 0),
                'paid_invoices' => (int) ($invoices['paid_invoices'] ?? 0),
            ],
            'thisMonth' => [
                'label' => now()->format('F Y'),
                'bookings' => (int) ($bookings['this_month'] ?? 0),
                'gmv_lkr' => (int) ($bookings['gmv_this_month'] ?? 0),
                'commission_lkr' => (int) ($invoices['commission_this_month'] ?? 0),
                'new_users' => (int) ($users['new_this_month'] ?? 0),
                'new_partners' => (int) ($businesses['new_this_month'] ?? 0),
            ],
            'attention' => [
                'disputes_ready' => (int) ($disputes['ready'] ?? 0),
                'disputes_awaiting' => (int) ($disputes['awaiting'] ?? 0),
                'tickets_waiting' => $ticketsWaiting,
                'receipts_pending' => $receiptsPending,
                'partners_pending' => (int) ($businesses['pending'] ?? 0),
                'escalated' => (int) ($bookings['escalated'] ?? 0),
            ],
            'months' => $this->monthly(),
            'topPartners' => $this->topPartners(),
        ]);
    }

    /**
     * Six months of bookings, GMV and commission in two grouped queries.
     *
     * @return array<int, array<string, mixed>>
     */
    private function monthly(): array
    {
        $since = now()->subMonths(5)->startOfMonth();
        $bucket = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "date_format(created_at, '%Y-%m')";

        $bookingRows = DB::table('bookings')
            ->where('created_at', '>=', $since)
            ->selectRaw($bucket.' as bucket')
            ->selectRaw('count(*) as bookings')
            ->selectRaw('sum(case when status in (?, ?) then total_lkr else 0 end) as gmv', [BookingStatus::Confirmed->value, BookingStatus::Completed->value])
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket');

        $invoiceRows = DB::table('invoices')
            ->where('created_at', '>=', $since)
            ->selectRaw($bucket.' as bucket')
            ->selectRaw('sum(amount_lkr) as commission')
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket');

        $months = [];

        for ($offset = 5; $offset >= 0; $offset--) {
            $start = now()->subMonths($offset)->startOfMonth();
            $key = $start->format('Y-m');

            $bookings = (array) ($bookingRows[$key] ?? []);
            $invoices = (array) ($invoiceRows[$key] ?? []);

            $months[] = [
                'label' => $start->format('M'),
                'bookings' => (int) ($bookings['bookings'] ?? 0),
                'gmv_lkr' => (int) ($bookings['gmv'] ?? 0),
                'commission_lkr' => (int) ($invoices['commission'] ?? 0),
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
