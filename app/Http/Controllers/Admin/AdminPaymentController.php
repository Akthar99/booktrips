<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\ReceiptStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateReceiptRequest;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Services\CommissionService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminPaymentController extends Controller
{
    public function __construct(
        private readonly CommissionService $commission,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * All commission invoices and partner receipts.
     */
    public function index(): Response
    {
        $invoices = Invoice::query()
            ->with(['business', 'receipts'])
            ->orderByDesc('period')
            ->get()
            ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'period' => $invoice->period,
                'period_label' => $this->commission->monthLabel($invoice->period),
                'due_date' => $this->commission->lastDayOfPeriod($invoice->period),
                'amount_lkr' => $invoice->amount_lkr,
                'status' => $invoice->status->value,
                'paid_at' => $invoice->paid_at?->toISOString(),
                'business_name' => $invoice->business->name ?? '',
                'receipts' => $invoice->receipts
                    ->map(fn (Receipt $receipt): array => [
                        'id' => $receipt->id,
                        'status' => $receipt->status->value,
                        'created_at' => $receipt->created_at?->toISOString(),
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();

        $receipts = Receipt::query()
            ->with(['business', 'invoice'])
            ->latest()
            ->take(100)
            ->get()
            ->map(fn (Receipt $receipt): array => [
                'id' => $receipt->id,
                'status' => $receipt->status->value,
                'note' => $receipt->note,
                'original_name' => $receipt->original_name,
                'created_at' => $receipt->created_at?->toISOString(),
                'reviewed_at' => $receipt->reviewed_at?->toISOString(),
                'business_name' => $receipt->business->name ?? '',
                'period' => $receipt->invoice->period ?? '',
                'amount_lkr' => $receipt->invoice->amount_lkr ?? 0,
                'file_url' => route('receipts.download', $receipt),
            ])
            ->all();

        return Inertia::render('admin/payments', [
            'bank' => config('booktrips.bank'),
            'commissionRate' => config('booktrips.commission_rate'),
            'invoices' => $invoices,
            'receipts' => $receipts,
            'summary' => [
                'billed_lkr' => (int) Invoice::query()->sum('amount_lkr'),
                'collected_lkr' => (int) Invoice::query()->where('status', InvoiceStatus::Paid)->sum('amount_lkr'),
                'outstanding_lkr' => (int) Invoice::query()->where('status', '!=', InvoiceStatus::Paid)->sum('amount_lkr'),
                'overdue_lkr' => (int) Invoice::query()
                    ->where('status', '!=', InvoiceStatus::Paid)
                    ->where('created_at', '<', now()->startOfMonth())
                    ->sum('amount_lkr'),
                'pending_receipts' => (int) Receipt::query()->where('status', ReceiptStatus::Pending)->count(),
            ],
        ]);
    }

    /**
     * Confirm or reject a receipt. Confirming marks the invoice paid.
     */
    public function updateReceipt(UpdateReceiptRequest $request, Receipt $receipt): RedirectResponse
    {
        $status = ReceiptStatus::from($request->string('status')->value());

        $receipt->forceFill([
            'status' => $status,
            'reviewed_at' => now(),
        ])->save();

        $invoice = $receipt->invoice;

        if ($invoice) {
            if ($status === ReceiptStatus::Confirmed) {
                $invoice->forceFill([
                    'status' => InvoiceStatus::Paid,
                    'paid_at' => now(),
                ])->save();
            } elseif ($status === ReceiptStatus::Rejected) {
                $invoice->forceFill(['status' => InvoiceStatus::Open])->save();
            }
        }

        $business = $receipt->business;

        if ($business && $invoice) {
            $confirmed = $status === ReceiptStatus::Confirmed;

            $this->notifications->notifyBusinessOwner(
                $business,
                'receipt',
                $confirmed ? 'Commission payment confirmed' : 'Receipt needs another look',
                $confirmed
                    ? 'Thanks! Your payment for '.$this->commission->monthLabel($invoice->period).' is marked paid.'
                    : 'We could not match the uploaded receipt to this invoice. Please upload a clearer copy.',
                null,
                '/partners/payments',
            );
        }

        return back()->with('success', 'Receipt reviewed.');
    }
}
