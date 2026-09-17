<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Mail\SupportReplyMail;
use App\Models\Booking;
use App\Models\Business;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Support threads between users and the BookTrips team.
 */
class SupportService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly MailService $mail,
    ) {}

    /**
     * @param  array{subject: string, category: string, body: string, booking_id?: int|null}  $data
     */
    public function open(User $user, array $data): SupportTicket
    {
        $bookingId = $data['booking_id'] ?? null;

        if ($bookingId !== null) {
            $businessId = $user->business instanceof Business ? $user->business->id : 0;

            $owns = Booking::query()
                ->whereKey($bookingId)
                ->where(function ($query) use ($user, $businessId): void {
                    $query->where('user_id', $user->id)
                        ->orWhere('business_id', $businessId);
                })
                ->exists();

            if (! $owns) {
                throw ValidationException::withMessages(['booking_id' => 'That booking is not yours.']);
            }
        }

        return DB::transaction(function () use ($user, $data, $bookingId): SupportTicket {
            $ticket = SupportTicket::create([
                'user_id' => $user->id,
                'booking_id' => $bookingId,
                'subject' => $data['subject'],
                'category' => $data['category'],
                'status' => TicketStatus::AwaitingAdmin,
                'last_message_at' => now(),
            ]);

            SupportMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'is_staff' => false,
                'body' => $data['body'],
            ]);

            $this->notifications->notifyAdmins(
                'support',
                'New support request',
                $ticket->subject,
                null,
                '/admin/support?ticket='.$ticket->id,
            );

            return $ticket;
        });
    }

    /**
     * Add a message to a thread and hand the ball to the other side.
     */
    public function reply(SupportTicket $ticket, User $author, string $body, bool $staff): SupportMessage
    {
        if (! $ticket->isOpen() && ! $staff) {
            throw ValidationException::withMessages(['body' => 'This request is closed. Open a new one if you still need help.']);
        }

        $message = DB::transaction(function () use ($ticket, $author, $body, $staff): SupportMessage {
            $message = SupportMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => $author->id,
                'is_staff' => $staff,
                'body' => $body,
            ]);

            $ticket->forceFill([
                'status' => $staff ? TicketStatus::AwaitingUser : TicketStatus::AwaitingAdmin,
                'last_message_at' => now(),
            ])->save();

            return $message;
        });

        if ($staff) {
            $owner = $ticket->user;

            if ($owner) {
                $this->notifications->notify(
                    $owner,
                    'support',
                    'BookTrips replied to your request',
                    $ticket->subject,
                    null,
                    '/support?ticket='.$ticket->id,
                );

                $this->mail->quietSend($owner->email, new SupportReplyMail($ticket, $message));
            }
        } else {
            $this->notifications->notifyAdmins(
                'support',
                'Support request updated',
                $ticket->subject,
                null,
                '/admin/support?ticket='.$ticket->id,
            );
        }

        return $message;
    }

    public function setStatus(SupportTicket $ticket, TicketStatus $status): SupportTicket
    {
        $ticket->forceFill(['status' => $status])->save();

        if ($status === TicketStatus::Resolved) {
            $owner = $ticket->user;

            if ($owner) {
                $this->notifications->notify(
                    $owner,
                    'support',
                    'Support request resolved',
                    $ticket->subject,
                    null,
                    '/support?ticket='.$ticket->id,
                );
            }
        }

        return $ticket;
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(SupportTicket $ticket, bool $withMessages = true): array
    {
        $payload = [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'category' => $ticket->category->value,
            'category_label' => $ticket->category->label(),
            'status' => $ticket->status->value,
            'status_label' => $ticket->status->label(),
            'booking_id' => $ticket->booking_id,
            'user' => $ticket->user ? [
                'id' => $ticket->user->id,
                'name' => $ticket->user->name,
                'email' => $ticket->user->email,
                'role' => $ticket->user->role->value,
            ] : null,
            'last_message_at' => $ticket->last_message_at?->toISOString(),
            'created_at' => $ticket->created_at?->toISOString(),
        ];

        if ($withMessages) {
            $payload['messages'] = $ticket->messages
                ->map(fn (SupportMessage $message): array => [
                    'id' => $message->id,
                    'body' => $message->body,
                    'is_staff' => $message->is_staff,
                    'author' => $message->is_staff
                        ? 'BookTrips team'
                        : ($message->author instanceof User ? $message->author->name : 'You'),
                    'created_at' => $message->created_at?->toISOString(),
                ])
                ->all();
        }

        return $payload;
    }
}
