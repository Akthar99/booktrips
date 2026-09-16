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

class HostBookingNoticeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public Package $package,
        public bool $isReminder = false,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->isReminder
            ? "Reminder: booking request {$this->booking->booking_code} is still unanswered"
            : "New booking request · {$this->booking->booking_code}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.host-booking-notice',
            with: [
                'guestName' => $this->booking->guest_name,
                'packageTitle' => $this->package->title,
                'checkIn' => $this->booking->check_in->toDateString(),
                'checkOut' => $this->booking->check_out->toDateString(),
                'guests' => $this->booking->guests,
                'bookingCode' => $this->booking->booking_code,
                'isReminder' => $this->isReminder,
            ],
        );
    }
}
