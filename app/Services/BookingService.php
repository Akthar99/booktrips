<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Mail\BookingDecisionMail;
use App\Mail\BookingRequestedMail;
use App\Mail\HostBookingNoticeMail;
use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class BookingService
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly ScheduleService $schedule,
        private readonly CommissionService $commission,
        private readonly NotificationService $notifications,
        private readonly MailService $mail,
        private readonly SmsService $sms,
        private readonly PhoneVerificationService $phones,
    ) {}

    /**
     * Create a booking request. All money fields are recomputed server-side.
     *
     * @param  array{check_in: string, check_out: string, guests: int, guest_name: string, guest_phone: string, notes?: string|null}  $data
     */
    public function create(User $user, Package $package, array $data): Booking
    {
        $package->loadMissing('business');

        if (! $package->active) {
            throw ValidationException::withMessages([
                'package' => 'This package is not available right now.',
            ]);
        }

        $guests = (int) $data['guests'];

        if ($guests < $package->min_guests || $guests > $package->max_guests) {
            throw ValidationException::withMessages([
                'guests' => "This package is for {$package->min_guests}–{$package->max_guests} guests.",
            ]);
        }

        if (! $this->schedule->allowsStay($package, $data['check_in'], $data['check_out'])) {
            throw ValidationException::withMessages([
                'check_in' => 'Those dates are outside this package’s running days.',
            ]);
        }

        $pricing = $this->pricing->calculateTotal($package, $guests, $data['check_in'], $data['check_out']);

        $booking = DB::transaction(function () use ($user, $package, $data, $guests, $pricing): Booking {
            $this->assertCapacity($package, $data['check_in'], $data['check_out'], $guests);

            return Booking::create([
                'booking_code' => $this->generateCode(),
                'user_id' => $user->id,
                'package_id' => $package->id,
                'business_id' => $package->business_id,
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'guests' => $guests,
                'base_total_lkr' => $pricing['base_total_lkr'],
                'discount_lkr' => $pricing['discount_lkr'],
                'discount_applied' => $pricing['discount_active'],
                'discount_type' => $pricing['discount_type'],
                'discount_value' => $pricing['discount_value'],
                'total_lkr' => $pricing['total_lkr'],
                'status' => BookingStatus::Requested,
                'payment_method' => 'pay_at_destination',
                'guest_name' => trim($data['guest_name']),
                'guest_phone' => trim($data['guest_phone']),
                'notes' => isset($data['notes']) ? (trim((string) $data['notes']) ?: null) : null,
            ]);
        });

        $this->mail->quietSend($user->email, new BookingRequestedMail($booking, $package));

        $business = $package->business;

        if ($business) {
            $this->notifications->notifyBusinessOwner(
                $business,
                'booking',
                "New request {$booking->booking_code}",
                "{$booking->guest_name} · {$booking->guests} guests · {$booking->check_in->toDateString()}",
                $booking->id,
                route('partner.bookings.show', $booking),
            );

            $this->mail->quietSend($business->email, new HostBookingNoticeMail($booking, $package));

            $phone = $this->phones->normalise($business->phone);

            if ($phone !== null) {
                $this->sms->queue($phone, "New BookTrips request {$booking->booking_code}: {$booking->guest_name}, {$booking->guests} guests, {$booking->check_in->toDateString()}. Confirm in your dashboard.");
            }
        }

        return $booking;
    }

    /**
     * Move a booking to a new status, enforcing who may do what.
     */
    public function updateStatus(Booking $booking, BookingStatus $status, User $actor): Booking
    {
        if ($booking->status === $status) {
            return $booking;
        }

        $booking->loadMissing(['user', 'package']);

        if (! $this->canTransition($booking, $status, $actor)) {
            throw ValidationException::withMessages([
                'status' => 'That status change is not allowed for this booking.',
            ]);
        }

        $booking->forceFill(['status' => $status])->save();

        if ($status === BookingStatus::Completed) {
            $this->commission->add($booking);
        }

        if (in_array($status, [BookingStatus::Confirmed, BookingStatus::Rejected], true)) {
            $this->mail->quietSend($booking->user?->email, new BookingDecisionMail($booking->refresh(), $status));
        }

        if ($status === BookingStatus::Confirmed) {
            $this->textTravellerAboutConfirmation($booking->refresh());
        }

        $this->notifyTraveller($booking->refresh(), $status);

        return $booking->refresh();
    }

    /**
     * A traveller cancels one of their own bookings.
     */
    public function cancelByGuest(Booking $booking, User $user): Booking
    {
        if ($booking->user_id !== $user->id) {
            throw new AuthorizationException('You cannot cancel this booking.');
        }

        if ($booking->status === BookingStatus::Cancelled) {
            return $booking;
        }

        if (! in_array($booking->status, BookingStatus::guestCancellable(), true)) {
            throw ValidationException::withMessages([
                'status' => 'This booking can no longer be cancelled.',
            ]);
        }

        $booking->forceFill(['status' => BookingStatus::Cancelled])->save();

        $this->tellHostAboutCancellation($booking->refresh());

        return $booking;
    }

    /**
     * Tell the host their calendar just freed up.
     */
    private function tellHostAboutCancellation(Booking $booking): void
    {
        $booking->loadMissing('package.business');

        $business = $booking->package?->business;

        if (! $business) {
            return;
        }

        $this->notifications->notifyBusinessOwner(
            $business,
            'booking',
            "Booking {$booking->booking_code} cancelled",
            "{$booking->guest_name} released {$booking->check_in->toDateString()} · {$booking->guests} guests.",
            $booking->id,
            route('partner.bookings.show', $booking),
        );

        $phone = $this->phones->normalise($business->phone);

        if ($phone !== null) {
            $this->sms->queue(
                $phone,
                "BookTrips: {$booking->guest_name} cancelled booking {$booking->booking_code} on {$booking->check_in->toDateString()}. Those dates are free again.",
            );
        }
    }

    /**
     * The host accepted the request — text the traveller so they hear it now.
     */
    private function textTravellerAboutConfirmation(Booking $booking): void
    {
        $booking->loadMissing('user');

        $phone = $this->phones->normalise($booking->guest_phone)
            ?? $this->phones->normalise($booking->user?->phone);

        if ($phone !== null) {
            $this->sms->queue(
                $phone,
                "BookTrips: booking {$booking->booking_code} on {$booking->check_in->toDateString()} is confirmed. Pay the host when you arrive.",
            );
        }
    }

    /**
     * Keep the traveller posted in-app when someone else moves their booking.
     */
    private function notifyTraveller(Booking $booking, BookingStatus $status): void
    {
        $booking->loadMissing(['user', 'package']);

        $traveller = $booking->user;

        if (! $traveller) {
            return;
        }

        $messages = [
            'confirmed' => ['Booking confirmed', "{$booking->package?->title} on {$booking->check_in->toDateString()} is confirmed. Pay the host at the destination."],
            'rejected' => ['Booking could not be confirmed', "The host could not take {$booking->booking_code}. Try other dates or another package."],
            'cancelled' => ['Booking cancelled', "{$booking->booking_code} was cancelled by the host. Try other dates or another package."],
            'completed' => ['Trip finished', "How was {$booking->package?->title}? Leave a review for other travellers."],
        ];

        if (! isset($messages[$status->value])) {
            return;
        }

        [$title, $body] = $messages[$status->value];

        $this->notifications->notify(
            $traveller,
            'booking',
            $title,
            $body,
            $booking->id,
            route('bookings.show', $booking),
        );
    }

    private function canTransition(Booking $booking, BookingStatus $to, User $actor): bool
    {
        if ($actor->isAdmin()) {
            return true;
        }

        $allowed = match ($booking->status) {
            BookingStatus::Requested => [BookingStatus::Confirmed, BookingStatus::Rejected, BookingStatus::Cancelled],
            BookingStatus::Confirmed => [BookingStatus::Completed, BookingStatus::Cancelled],
            default => [],
        };

        return in_array($to, $allowed, true);
    }

    /**
     * Guard against overbooking a package across overlapping dates.
     */
    private function assertCapacity(Package $package, string $checkIn, string $checkOut, int $guests): void
    {
        if (! config('booktrips.capacity_guard')) {
            return;
        }

        $booked = (int) Booking::query()
            ->where('package_id', $package->id)
            ->overlapping($checkIn, $checkOut)
            ->sum('guests');

        if ($booked + $guests > $package->max_guests) {
            throw ValidationException::withMessages([
                'check_in' => 'Those dates are fully booked for this package. Try different dates or contact the host.',
            ]);
        }
    }

    private function generateCode(): string
    {
        for ($attempt = 0; $attempt < 8; $attempt++) {
            $code = 'BT-'.strtoupper(bin2hex(random_bytes(3)));

            if (! Booking::query()->where('booking_code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('Unable to generate a unique booking code.');
    }
}
