<?php

namespace App\Mail;

use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PartnerApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Business $business) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your BookTrips partner account is approved');
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.partner-approved',
            with: [
                'name' => $this->business->user->name ?? '',
                'businessName' => $this->business->name,
                'dashboardUrl' => route('partner.dashboard'),
                'newPackageUrl' => route('partner.packages.create'),
            ],
        );
    }
}
