<?php

namespace App\Services;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailService
{
    /**
     * Queue a mail without ever failing the calling request if mail is down.
     */
    public function quietSend(?string $email, ?Mailable $mailable): void
    {
        if (! $email || ! $mailable) {
            return;
        }

        try {
            Mail::to($email)->queue($mailable);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
