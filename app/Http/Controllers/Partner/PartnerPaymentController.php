<?php

namespace App\Http\Controllers\Partner;

use App\Enums\InvoiceStatus;
use App\Enums\ReceiptStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Partner\StoreReceiptRequest;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Services\CommissionService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PartnerPaymentController extends Controller
{
    public function __construct(
        private readonly CommissionService $commission,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Monthly commission invoices and their receipts.
     */
    public function index(Request $request): Response
    {
        $business = $request->user()->business;

        $invoices = Invoice::query()
            ->where('business_id', $business->id)
            ->with('receipts')
            ->orderByDesc('period')
            ->get()
            ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'period' => $invoice->period,
                'period_label' => $this->commission->monthLabel($invoice->period),
                'due_date' => $this->commission->lastDayOfPeriod($invoice->period),
                'amount_lkr' => $invoice->amount_lkr,
                'status' => $invoice->status->value,
                'lines' => $invoice->lines ?? [],
                'paid_at' => $invoice->paid_at?->toISOString(),
                'receipts' => $invoice->receipts
                    ->map(fn (Receipt $receipt): array => $this->receipt($receipt))
                    ->values()
                    ->all(),
            ])
            ->all();

        return Inertia::render('partner/payments', [
            'bank' => config('booktrips.bank'),
            'commissionRate' => config('booktrips.commission_rate'),
            'invoices' => $invoices,
        ]);
    }

    /**
     * Upload a bank-transfer receipt for an invoice.
     */
    public function store(StoreReceiptRequest $request): RedirectResponse
    {
        $business = $request->user()->business;
        $invoice = Invoice::query()->findOrFail($request->integer('invoice_id'));

        $this->authorize('view', $invoice);

        if ($invoice->status === InvoiceStatus::Paid) {
            throw ValidationException::withMessages([
                'invoice_id' => 'This invoice is already paid.',
            ]);
        }

        $file = $request->file('file');
        $path = $file->store("receipts/{$business->id}", (string) config('booktrips.storage.receipts_disk', 'local'));

        Receipt::create([
            'invoice_id' => $invoice->id,
            'business_id' => $business->id,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'note' => $request->input('note'),
            'status' => ReceiptStatus::Pending,
        ]);

        $invoice->forceFill(['status' => InvoiceStatus::Submitted])->save();

        $this->notifications->notifyAdmins(
            'receipt',
            "Receipt uploaded: {$business->name}",
            $this->commission->monthLabel($invoice->period).' · Rs. '.number_format($invoice->amount_lkr).' waiting for review.',
            null,
            '/admin/payments',
        );

        return back()->with('success', 'Receipt uploaded. We will review it shortly.');
    }

    /**
     * @return array<string, mixed>
     */
    private function receipt(Receipt $receipt): array
    {
        return [
            'id' => $receipt->id,
            'note' => $receipt->note,
            'original_name' => $receipt->original_name,
            'status' => $receipt->status->value,
            'created_at' => $receipt->created_at?->toISOString(),
            'reviewed_at' => $receipt->reviewed_at?->toISOString(),
            'file_url' => route('receipts.download', $receipt),
        ];
    }
}
