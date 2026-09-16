<?php

namespace App\Mail;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingDecisionMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public BookingStatus $status,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->status === BookingStatus::Confirmed
            ? "Booking confirmed · {$this->booking->booking_code}"
            : "Booking {$this->status->value} · {$this->booking->booking_code}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.booking-decision',
            with: [
                'name' => $this->booking->user->name ?? $this->booking->guest_name,
                'bookingCode' => $this->booking->booking_code,
                'status' => $this->status->value,
                'confirmed' => $this->status === BookingStatus::Confirmed,
                'packageTitle' => $this->booking->package->title ?? 'your package',
            ],
        );
    }
}
