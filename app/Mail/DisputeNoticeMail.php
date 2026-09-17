<?php

namespace App\Mail;

use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * One flexible notification for every step of a dispute: opened, answered, resolved.
 */
class DisputeNoticeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Dispute $dispute,
        public string $headline,
        public string $message,
        public string $url,
    ) {}

    public function envelope(): Envelope
    {
        $code = $this->dispute->booking->booking_code;

        return new Envelope(subject: trim("{$this->headline} · {$code}", ' ·'));
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.dispute-notice',
            with: [
                'name' => $this->dispute->against->name,
                'headline' => $this->headline,
                'body' => $this->message,
                'url' => $this->url,
                'code' => $this->dispute->booking->booking_code,
                'deadline' => $this->dispute->response_deadline_at?->toDayDateTimeString(),
            ],
        );
    }
}
