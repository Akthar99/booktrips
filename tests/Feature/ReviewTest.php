<?php

use App\Models\Booking;
use App\Models\Business;
use App\Models\Package;
use App\Models\Review;
use App\Models\User;

beforeEach(function () {
    $this->business = Business::factory()->for(User::factory()->partner(), 'user')->create();
    $this->package = Package::factory()->for($this->business)->create(['max_guests' => 6]);
    $this->traveller = User::factory()->create();
});

it('publishes one review per finished booking and refreshes the rating', function () {
    $booking = Booking::factory()->for($this->traveller)->forPackage($this->package)->completed()->create();

    $this->actingAs($this->traveller)
        ->post('/reviews', [
            'booking_id' => $booking->id,
            'rating' => 5,
            'title' => 'Great day',
            'comment' => 'Guides were on time and lunch was huge.',
        ])
        ->assertRedirect(route('bookings.show', $booking));

    $this->package->refresh();

    expect(Review::query()->count())->toBe(1)
        ->and($this->package->review_count)->toBe(1)
        ->and($this->package->rating)->toBe(5.0);

    $this->actingAs($this->traveller)
        ->post('/reviews', [
            'booking_id' => $booking->id,
            'rating' => 4,
            'comment' => 'Trying again.',
        ])
        ->assertSessionHasErrors('booking_id');
});

it('only allows reviews on your own completed booking', function () {
    $unfinished = Booking::factory()->for($this->traveller)->forPackage($this->package)->create();

    $this->actingAs($this->traveller)
        ->post('/reviews', ['booking_id' => $unfinished->id, 'rating' => 5, 'comment' => 'Too early.'])
        ->assertSessionHasErrors('booking_id');

    $someoneElse = User::factory()->create();
    $completed = Booking::factory()->for($this->traveller)->forPackage($this->package)->completed()->create();

    $this->actingAs($someoneElse)
        ->post('/reviews', ['booking_id' => $completed->id, 'rating' => 5, 'comment' => 'Not mine.'])
        ->assertSessionHasErrors('booking_id');
});

it('validates the rating and comment length', function () {
    $booking = Booking::factory()->for($this->traveller)->forPackage($this->package)->completed()->create();

    $this->actingAs($this->traveller)
        ->post('/reviews', ['booking_id' => $booking->id, 'rating' => 9, 'comment' => 'Out of range'])
        ->assertSessionHasErrors('rating');

    $this->actingAs($this->traveller)
        ->post('/reviews', ['booking_id' => $booking->id, 'rating' => 4, 'comment' => str_repeat('a', 1201)])
        ->assertSessionHasErrors('comment');
});
