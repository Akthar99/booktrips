<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    /**
     * Add the finished booking's commission to the partner's monthly invoice.
     */
    public function add(Booking $booking): ?Invoice
    {
        if ($booking->commission_added) {
            return null;
        }

        $package = $booking->package;

        if (! $package) {
            return null;
        }

        $rate = (float) config('booktrips.commission_rate');
        $amount = (int) round($booking->total_lkr * $rate);
        $period = $this->monthKey($booking->check_out ?? $booking->created_at);

        $invoice = DB::transaction(function () use ($booking, $package, $period, $amount): Invoice {
            $line = [
                'booking_id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'amount_lkr' => $amount,
                'total_lkr' => $booking->total_lkr,
            ];

            $invoice = Invoice::query()
                ->where('business_id', $package->business_id)
                ->where('period', $period)
                ->lockForUpdate()
                ->first();

            if (! $invoice) {
                $invoice = Invoice::create([
                    'business_id' => $package->business_id,
                    'period' => $period,
                    'amount_lkr' => $amount,
                    'status' => InvoiceStatus::Open,
                    'lines' => [$line],
                ]);
            } elseif ($invoice->status !== InvoiceStatus::Paid) {
                $invoice->update([
                    'amount_lkr' => $invoice->amount_lkr + $amount,
                    'lines' => [...($invoice->lines ?? []), $line],
                ]);
            } else {
                // The month was already settled — track the late commission separately.
                $invoice = Invoice::create([
                    'business_id' => $package->business_id,
                    'period' => $period.'-adj',
                    'amount_lkr' => $amount,
                    'status' => InvoiceStatus::Open,
                    'lines' => [$line],
                ]);
            }

            $booking->forceFill([
                'commission_added' => true,
                'commission_lkr' => $amount,
            ])->save();

            return $invoice;
        });

        return $invoice;
    }

    /**
     * Bill a penalty (a dispute the partner lost) on their current invoice.
     */
    public function addPenalty(Business $business, int $amountLkr, string $reason): Invoice
    {
        $period = $this->monthKey();

        return DB::transaction(function () use ($business, $amountLkr, $reason, $period): Invoice {
            $line = [
                'booking_id' => null,
                'booking_code' => null,
                'reason' => $reason,
                'penalty' => true,
                'amount_lkr' => $amountLkr,
            ];

            $invoice = Invoice::query()
                ->where('business_id', $business->id)
                ->where('period', $period)
                ->lockForUpdate()
                ->first();

            if (! $invoice) {
                return Invoice::create([
                    'business_id' => $business->id,
                    'period' => $period,
                    'amount_lkr' => $amountLkr,
                    'status' => InvoiceStatus::Open,
                    'lines' => [$line],
                ]);
            }

            if ($invoice->status !== InvoiceStatus::Paid) {
                $invoice->update([
                    'amount_lkr' => $invoice->amount_lkr + $amountLkr,
                    'lines' => [...($invoice->lines ?? []), $line],
                ]);

                return $invoice;
            }

            // Already settled: keep the penalty on its own adjustment invoice.
            return Invoice::create([
                'business_id' => $business->id,
                'period' => $period.'-pen',
                'amount_lkr' => $amountLkr,
                'status' => InvoiceStatus::Open,
                'lines' => [$line],
            ]);
        });
    }

    public function monthKey(string|Carbon|null $date = null): string
    {
        return Carbon::parse($date ?? now())->format('Y-m');
    }

    public function monthLabel(string $period): string
    {
        $base = str_ends_with($period, '-adj') ? substr($period, 0, -4) : $period;

        [$year, $month] = array_pad(explode('-', $base), 2, '1');

        $label = Carbon::create((int) $year, max((int) $month, 1), 1)->format('F Y');

        return str_ends_with($period, '-adj') ? $label.' (adjustment)' : $label;
    }

    public function lastDayOfPeriod(string $period): string
    {
        $base = str_ends_with($period, '-adj') ? substr($period, 0, -4) : $period;

        [$year, $month] = array_pad(explode('-', $base), 2, '1');

        return Carbon::create((int) $year, max((int) $month, 1), 1)->endOfMonth()->toDateString();
    }
}
