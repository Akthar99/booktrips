<?php

namespace App\Presenters;

use App\Models\Booking;
use App\Models\Review;

class BookingPresenter
{
    /**
     * A booking for the traveller who owns it.
     *
     * @return array<string, mixed>
     */
    public function forGuest(Booking $booking, ?Review $review = null): array
    {
        return [
            ...$this->base($booking),
            'guest_email' => $booking->user->email ?? '',
            'package' => $this->packageSummary($booking),
            'host' => $booking->package?->business ? [
                'id' => $booking->package->business->id,
                'name' => $booking->package->business->name,
                'phone' => $booking->package->business->phone,
                'email' => $booking->package->business->email,
            ] : null,
            'review' => $review ? $this->review($review) : null,
        ];
    }

    /**
     * A booking for the owning partner, with contact details hidden until confirmed.
     *
     * @return array<string, mixed>
     */
    public function forPartner(Booking $booking): array
    {
        $expose = $booking->status->exposesGuestContact();

        return [
            ...$this->base($booking),
            'guest_phone' => $expose ? $booking->guest_phone : '',
            'guest_email' => $expose ? ($booking->user->email ?? '') : '',
            'contact_hidden' => ! $expose,
            'package' => $this->packageSummary($booking),
        ];
    }

    /**
     * A booking for super admins — never redacted.
     *
     * @return array<string, mixed>
     */
    public function forAdmin(Booking $booking): array
    {
        $business = $booking->package?->business;

        return [
            ...$this->base($booking),
            'guest_email' => $booking->user->email ?? '',
            'package' => $this->packageSummary($booking),
            'host' => $business ? [
                'id' => $business->id,
                'name' => $business->name,
                'phone' => $business->phone,
                'email' => $business->email,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function base(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'booking_code' => $booking->booking_code,
            'check_in' => $booking->check_in->toDateString(),
            'check_out' => $booking->check_out->toDateString(),
            'guests' => $booking->guests,
            'base_total_lkr' => $booking->base_total_lkr,
            'discount_lkr' => $booking->discount_lkr,
            'discount_applied' => $booking->discount_applied,
            'discount_label' => $booking->discount_applied
                ? ($booking->discount_type->value === 'percentage'
                    ? number_format($booking->discount_value, 0).'% off'
                    : 'Rs. '.number_format($booking->discount_lkr).' off')
                : '',
            'total_lkr' => $booking->total_lkr,
            'status' => $booking->status->value,
            'payment_method' => $booking->payment_method,
            'guest_name' => $booking->guest_name,
            'guest_phone' => $booking->guest_phone,
            'notes' => $booking->notes,
            'commission_added' => $booking->commission_added,
            'commission_lkr' => $booking->commission_lkr,
            'escalated' => $booking->escalated,
            'created_at' => $booking->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function packageSummary(Booking $booking): ?array
    {
        $package = $booking->package;

        if (! $package) {
            return null;
        }

        return [
            'id' => $package->id,
            'title' => $package->title,
            'slug' => $package->slug,
            'location' => $package->location,
            'images' => $package->images ?? [],
            'category' => $package->category,
            'business_id' => $package->business_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function review(Review $review): array
    {
        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'title' => $review->title,
            'comment' => $review->comment,
            'created_at' => $review->created_at?->toISOString(),
        ];
    }
}
