<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\DisputePenalty;
use App\Enums\DisputeResolution;
use App\Enums\DisputeStatus;
use App\Enums\DisputeType;
use App\Mail\DisputeNoticeMail;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Dispute;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reports between travellers and partners, with a response window before any
 * verdict, and the penalties that follow one.
 */
class DisputeService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly MailService $mail,
        private readonly CommissionService $commission,
    ) {}

    /**
     * Whether this user can raise a report on this booking right now.
     */
    public function canReport(User $actor, Booking $booking): bool
    {
        if (! in_array($booking->status, [BookingStatus::Confirmed, BookingStatus::Completed], true)) {
            return false;
        }

        if ($booking->check_in->isFuture()) {
            return false;
        }

        if ($booking->disputes()->open()->exists()) {
            return false;
        }

        if ($actor->business && $booking->business_id === $actor->business->id) {
            return true;
        }

        return $booking->user_id === $actor->id;
    }

    /**
     * Open a report between the two sides of a booking.
     *
     * @param  array{type: string, summary: string, details?: string|null}  $data
     */
    public function open(User $actor, Booking $booking, array $data): Dispute
    {
        $actor->loadMissing('business');
        $booking->loadMissing(['package.business.user', 'user']);

        // The business that owns the booking — not the actor's own business.
        $shop = $booking->package?->business;
        $isPartner = $actor->business !== null && $booking->business_id === $actor->business->id;

        if (! $isPartner && $booking->user_id !== $actor->id) {
            throw ValidationException::withMessages(['booking' => 'You are not part of this booking.']);
        }

        if (! in_array($booking->status, [BookingStatus::Confirmed, BookingStatus::Completed], true)) {
            throw ValidationException::withMessages([
                'booking' => 'Reports can only be raised for confirmed or finished bookings.',
            ]);
        }

        if ($booking->check_in->isFuture()) {
            throw ValidationException::withMessages([
                'booking' => 'Wait until the trip date has passed before reporting a problem.',
            ]);
        }

        $existing = Dispute::query()->where('booking_id', $booking->id)->open()->exists();

        if ($existing) {
            throw ValidationException::withMessages(['booking' => 'There is already an open report for this booking.']);
        }

        $type = DisputeType::from($data['type']);
        $allowed = $isPartner ? DisputeType::byPartner() : DisputeType::byTraveller();

        if (! in_array($type, $allowed, true)) {
            throw ValidationException::withMessages(['type' => 'That kind of report is not available to you.']);
        }

        $against = $isPartner
            ? $booking->user
            : $shop?->user;

        if (! $against) {
            throw ValidationException::withMessages(['booking' => 'We could not find the other party for this booking.']);
        }

        $hours = (int) config('booktrips.disputes.response_hours', 48);

        $dispute = DB::transaction(fn (): Dispute => Dispute::create([
            'booking_id' => $booking->id,
            'business_id' => $booking->business_id,
            'raised_by_user_id' => $actor->id,
            'against_user_id' => $against->id,
            'type' => $type,
            'status' => DisputeStatus::AwaitingResponse,
            'summary' => $data['summary'],
            'details' => $data['details'] ?? null,
            'response_deadline_at' => now()->addHours($hours),
        ]));

        $this->notifications->notifyAdmins(
            'dispute',
            "New report on {$booking->booking_code}",
            $dispute->summary,
            $booking->id,
            '/admin/disputes',
        );

        $this->notifyParty(
            $against,
            $dispute,
            'A report was opened about your booking',
            "{$booking->booking_code}: {$dispute->summary}. Tell us your side within {$hours} hours.",
            $this->linkFor($against, $booking),
        );

        return $dispute;
    }

    /**
     * The accused party explains what happened.
     *
     * @param  array<int, string>  $evidence
     */
    public function respond(Dispute $dispute, User $actor, string $response, array $evidence = []): Dispute
    {
        $dispute->loadMissing(['booking', 'raisedBy', 'against']);

        if (! $dispute->isOpen()) {
            throw ValidationException::withMessages(['response' => 'This report has already been settled.']);
        }

        if ($dispute->against_user_id !== $actor->id) {
            throw ValidationException::withMessages(['response' => 'Only the person this report is about can reply.']);
        }

        $dispute->forceFill([
            'response' => $response,
            'response_evidence' => $evidence === [] ? null : $evidence,
            'responded_at' => now(),
            'status' => DisputeStatus::UnderReview,
        ])->save();

        $booking = $dispute->booking;

        $this->notifications->notifyAdmins(
            'dispute',
            "Reply received on {$booking->booking_code}",
            'Both sides have now been heard — the report is ready for a decision.',
            $booking->id,
            '/admin/disputes',
        );

        $opener = $dispute->raisedBy;

        if ($opener) {
            $this->notifyParty(
                $opener,
                $dispute,
                'The other side replied to your report',
                "{$booking->booking_code}: both accounts are in. BookTrips will review the report.",
                $this->linkFor($opener, $booking),
            );
        }

        return $dispute->refresh();
    }

    /**
     * A super admin hands down the verdict and applies the penalty.
     *
     * @param  array{resolution: string, penalty?: string|null, penalty_amount_lkr?: int|null, resolution_note?: string|null}  $data
     */
    public function resolve(Dispute $dispute, User $admin, array $data): Dispute
    {
        $dispute->loadMissing(['booking', 'business', 'raisedBy', 'against']);

        if (! $dispute->canBeResolved()) {
            throw ValidationException::withMessages([
                'resolution' => 'Wait for the other side to respond, or for the response window to close.',
            ]);
        }

        $resolution = DisputeResolution::from($data['resolution']);
        $penalty = DisputePenalty::from($data['penalty'] ?? DisputePenalty::None->value);
        $amount = (int) ($data['penalty_amount_lkr'] ?? 0);

        DB::transaction(function () use ($dispute, $admin, $resolution, $penalty, $amount, $data): void {
            $dispute->forceFill([
                'status' => DisputeStatus::Resolved,
                'resolution' => $resolution,
                'penalty' => $penalty,
                'penalty_amount_lkr' => $amount > 0 ? $amount : null,
                'resolution_note' => $data['resolution_note'] ?? null,
                'resolved_by_user_id' => $admin->id,
                'resolved_at' => now(),
            ])->save();

            if ($resolution === DisputeResolution::CustomerFault) {
                $this->strikeTraveller($dispute, $penalty);
            }

            if ($resolution === DisputeResolution::PartnerFault) {
                $this->strikeBusiness($dispute, $penalty, $amount);
            }
        });

        $dispute->refresh();

        $booking = $dispute->booking;
        $verdict = $resolution->label().($penalty === DisputePenalty::None ? '' : ' · '.$penalty->label());
        $body = $dispute->resolution_note ?: 'The report on '.$booking->booking_code.' has been reviewed.';

        foreach ([$dispute->raisedBy, $dispute->against] as $party) {
            if (! $party) {
                continue;
            }

            $this->notifyParty($party, $dispute, 'Report resolved: '.$resolution->label(), $verdict.' — '.$body, $this->linkFor($party, $booking));
        }

        return $dispute;
    }

    /**
     * Three strikes on an account suspends it until BookTrips says otherwise.
     */
    private function strikeTraveller(Dispute $dispute, DisputePenalty $penalty): void
    {
        $user = $dispute->against;

        if (! $user) {
            return;
        }

        if ($penalty !== DisputePenalty::Strike && $penalty !== DisputePenalty::Suspend) {
            return;
        }

        $strikes = $penalty === DisputePenalty::Suspend ? 3 : $user->strikes + 1;

        $user->forceFill([
            'strikes' => $strikes,
            'active' => $strikes < 3,
        ])->save();
    }

    /**
     * Partner faults add a strike, suspend at three and can be billed as a penalty.
     */
    private function strikeBusiness(Dispute $dispute, DisputePenalty $penalty, int $amount): void
    {
        $business = $dispute->business;

        if (! $business) {
            return;
        }

        if ($penalty === DisputePenalty::Strike || $penalty === DisputePenalty::Suspend) {
            $strikes = $penalty === DisputePenalty::Suspend ? 3 : $business->strikes + 1;

            $business->forceFill([
                'strikes' => $strikes,
                'approved' => $strikes < 3 ? $business->approved : false,
            ])->save();
        }

        if ($amount > 0) {
            $this->commission->addPenalty(
                $business,
                $amount,
                'Dispute penalty · booking '.($dispute->booking->booking_code ?? ''),
            );
        }
    }

    private function notifyParty(User $user, Dispute $dispute, string $title, string $body, string $link): void
    {
        $this->notifications->notify($user, 'dispute', $title, $body, $dispute->booking_id, $link);

        $this->mail->quietSend($user->email, new DisputeNoticeMail($dispute, $title, $body, $link));
    }

    private function linkFor(User $user, Booking $booking): string
    {
        if ($user->isPartner()) {
            return route('partner.bookings.show', $booking);
        }

        return route('bookings.show', $booking);
    }

    /**
     * Everything one side of a report is allowed to see.
     *
     * @return array<string, mixed>
     */
    public function forParty(Dispute $dispute, User $user): array
    {
        return [
            ...$this->summary($dispute),
            'raised_by_me' => $dispute->raised_by_user_id === $user->id,
            'against_me' => $dispute->against_user_id === $user->id,
            'can_respond' => $dispute->isOpen() && $dispute->against_user_id === $user->id,
        ];
    }

    /**
     * Everything the admin console needs about one report.
     *
     * @return array<string, mixed>
     */
    public function summary(Dispute $dispute): array
    {
        return [
            'id' => $dispute->id,
            'type' => $dispute->type->value,
            'type_label' => $dispute->type->label(),
            'status' => $dispute->status->value,
            'summary' => $dispute->summary,
            'details' => $dispute->details,
            'response' => $dispute->response,
            'evidence' => $dispute->evidence ?? [],
            'response_evidence' => $dispute->response_evidence ?? [],
            'responded_at' => $dispute->responded_at?->toISOString(),
            'response_deadline_at' => $dispute->response_deadline_at?->toISOString(),
            'can_resolve' => $dispute->canBeResolved(),
            'resolution' => $dispute->resolution?->value,
            'resolution_label' => $dispute->resolution?->label(),
            'penalty' => $dispute->penalty?->value,
            'penalty_label' => $dispute->penalty?->label(),
            'penalty_amount_lkr' => $dispute->penalty_amount_lkr,
            'resolution_note' => $dispute->resolution_note,
            'resolved_at' => $dispute->resolved_at?->toISOString(),
            'created_at' => $dispute->created_at?->toISOString(),
        ];
    }
}
