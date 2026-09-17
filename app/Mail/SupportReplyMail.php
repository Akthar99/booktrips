<?php

namespace App\Mail;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportReplyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public SupportTicket $ticket,
        public SupportMessage $message,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'BookTrips replied: '.$this->ticket->subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.support-reply',
            with: [
                'name' => $this->ticket->user->name ?? 'there',
                'subject' => $this->ticket->subject,
                'body' => $this->message->body,
                'url' => route('support.index', ['ticket' => $this->ticket->id]),
            ],
        );
    }
}
