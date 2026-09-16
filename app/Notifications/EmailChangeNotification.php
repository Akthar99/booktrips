<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailChangeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $url) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirm your new BookTrips email')
            ->view('mail.email-change', [
                'name' => $notifiable instanceof User ? $notifiable->name : '',
                'url' => $this->url,
            ]);
    }
}
