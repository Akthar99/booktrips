<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    /**
     * Leave one review per completed booking and refresh the package rating.
     */
    public function store(StoreReviewRequest $request): RedirectResponse
    {
        $booking = Booking::query()->findOrFail($request->integer('booking_id'));

        if ($booking->user_id !== $request->user()->id) {
            throw ValidationException::withMessages([
                'booking_id' => 'Completed booking not found.',
            ]);
        }

        if ($booking->status !== BookingStatus::Completed) {
            throw ValidationException::withMessages([
                'booking_id' => 'You can review a package after the booking is marked finished.',
            ]);
        }

        if (Review::query()->where('booking_id', $booking->id)->exists()) {
            throw ValidationException::withMessages([
                'booking_id' => 'You have already reviewed this booking.',
            ]);
        }

        $package = Package::query()->findOrFail($booking->package_id);

        DB::transaction(function () use ($request, $booking, $package): void {
            Review::create([
                'booking_id' => $booking->id,
                'package_id' => $package->id,
                'user_id' => $request->user()->id,
                'rating' => $request->integer('rating'),
                'title' => $request->input('title'),
                'comment' => $request->string('comment')->value(),
            ]);

            $average = (float) Review::query()->where('package_id', $package->id)->avg('rating');
            $count = Review::query()->where('package_id', $package->id)->count();

            $package->forceFill([
                'rating' => round($average, 1),
                'review_count' => $count,
            ])->save();
        });

        return to_route('bookings.show', $booking)->with('success', 'Thanks! Your review is live.');
    }
}
