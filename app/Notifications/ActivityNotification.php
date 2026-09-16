<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ActivityNotification extends Notification
{
    use Queueable;

    /**
     * @param  array{type?: string, title: string, body?: string, booking_id?: int|null, link?: string|null}  $payload
     */
    public function __construct(public array $payload) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->payload['type'] ?? 'info',
            'title' => $this->payload['title'],
            'body' => $this->payload['body'] ?? '',
            'booking_id' => $this->payload['booking_id'] ?? null,
            'link' => $this->payload['link'] ?? null,
        ];
    }
}
