<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Package;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingRequestedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public Package $package,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Booking requested · {$this->booking->booking_code}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.booking-requested',
            with: [
                'name' => $this->booking->user->name ?? $this->booking->guest_name,
                'bookingCode' => $this->booking->booking_code,
                'packageTitle' => $this->package->title,
                'checkIn' => $this->booking->check_in->toDateString(),
                'checkOut' => $this->booking->check_out->toDateString(),
                'guests' => $this->booking->guests,
                'total' => number_format($this->booking->total_lkr),
            ],
        );
    }
}
