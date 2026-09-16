<?php

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Mail\BookingDecisionMail;
use App\Mail\BookingRequestedMail;
use App\Mail\HostBookingNoticeMail;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Mail::fake();
    Notification::fake();

    $this->partner = User::factory()->partner()->create();
    $this->business = Business::factory()->for($this->partner, 'user')->create(['approved' => true]);
    $this->package = Package::factory()->for($this->business)->create([
        'title' => 'Rafting day',
        'slug' => 'rafting-day',
        'price_lkr' => 10000,
        'price_type' => 'per_person',
        'min_guests' => 1,
        'max_guests' => 4,
        'active' => true,
    ]);
    $this->traveller = User::factory()->create();
});

function bookingPayload(Package $package, array $overrides = []): array
{
    return array_merge([
        'package_id' => $package->id,
        'check_in' => '2030-02-01',
        'check_out' => '2030-02-01',
        'guests' => 2,
        'guest_name' => 'Nimal Perera',
        'guest_phone' => '0771234567',
        'notes' => 'Vegetarian lunch',
    ], $overrides);
}

it('requires a verified email to book', function () {
    $traveller = User::factory()->unverified()->create();

    $this->actingAs($traveller)
        ->post('/bookings', bookingPayload($this->package))
        ->assertRedirect(route('verification.notice'));

    expect(Booking::query()->count())->toBe(0);
});

it('recomputes totals on the server and ignores client money fields', function () {
    $response = $this->actingAs($this->traveller)->post('/bookings', bookingPayload($this->package, [
        'total_lkr' => 1,
        'base_total_lkr' => 1,
        'status' => 'completed',
        'commission_lkr' => 999999,
        'commission_added' => true,
    ]));

    $booking = Booking::query()->firstOrFail();

    $response->assertRedirect(route('bookings.show', $booking));

    expect($booking->total_lkr)->toBe(20000)
        ->and($booking->base_total_lkr)->toBe(20000)
        ->and($booking->status)->toBe(BookingStatus::Requested)
        ->and($booking->business_id)->toBe($this->business->id)
        ->and($booking->commission_lkr)->toBe(0)
        ->and($booking->commission_added)->toBeFalse()
        ->and($booking->booking_code)->toStartWith('BT-');

    Mail::assertQueued(BookingRequestedMail::class);
    Mail::assertQueued(HostBookingNoticeMail::class);
});

it('blocks overbooking across overlapping dates', function () {
    Booking::factory()->forPackage($this->package)->create([
        'check_in' => '2030-02-01',
        'check_out' => '2030-02-01',
        'guests' => 4,
    ]);

    $this->actingAs($this->traveller)
        ->post('/bookings', bookingPayload($this->package, ['guests' => 1]))
        ->assertSessionHasErrors('check_in');

    expect(Booking::query()->count())->toBe(1);
});

it('validates guest range and dates', function () {
    $this->actingAs($this->traveller)
        ->post('/bookings', bookingPayload($this->package, ['guests' => 9]))
        ->assertSessionHasErrors('guests');

    $this->actingAs($this->traveller)
        ->post('/bookings', bookingPayload($this->package, ['check_in' => '2030-02-05', 'check_out' => '2030-02-01']))
        ->assertSessionHasErrors('check_out');
});

it('lets the partner confirm and hides contact details until then', function () {
    $booking = Booking::factory()->for($this->traveller)->forPackage($this->package)->create();

    $this->actingAs($this->partner)
        ->get('/partners/bookings')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('bookings.data.0.contact_hidden', true)
            ->where('bookings.data.0.guest_phone', ''));

    $this->actingAs($this->partner)
        ->patch("/partners/bookings/{$booking->id}/status", ['status' => 'confirmed'])
        ->assertRedirect();

    expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed);

    Mail::assertQueued(BookingDecisionMail::class);

    Notification::assertSentTo($this->traveller, ActivityNotification::class, function ($notification) use ($booking) {
        return $notification->payload['title'] === 'Booking confirmed'
            && $notification->payload['link'] === route('bookings.show', $booking);
    });

    $this->actingAs($this->partner)
        ->get('/partners/bookings')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('bookings.data.0.contact_hidden', false)
            ->where('bookings.data.0.guest_phone', $booking->guest_phone));
});

it('adds commission to the monthly invoice when a booking is finished', function () {
    $booking = Booking::factory()->for($this->traveller)->forPackage($this->package)->confirmed()->create();

    $this->actingAs($this->partner)
        ->patch("/partners/bookings/{$booking->id}/status", ['status' => 'completed'])
        ->assertRedirect();

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::Completed)
        ->and($booking->commission_added)->toBeTrue()
        ->and($booking->commission_lkr)->toBe(1000);

    $invoice = Invoice::query()->firstOrFail();

    expect($invoice->amount_lkr)->toBe(1000)
        ->and($invoice->status)->toBe(InvoiceStatus::Open)
        ->and($invoice->lines)->toHaveCount(1)
        ->and($invoice->lines[0]['booking_code'])->toBe($booking->booking_code);
});

it('keeps a settled month separate when commission arrives late', function () {
    $booking = Booking::factory()->for($this->traveller)->forPackage($this->package)->confirmed()->create([
        'check_out' => '2030-02-01',
    ]);

    Invoice::factory()->for($this->business)->paid()->create(['period' => '2030-02']);

    $this->actingAs($this->partner)
        ->patch("/partners/bookings/{$booking->id}/status", ['status' => 'completed']);

    expect(Invoice::query()->where('period', '2030-02-adj')->exists())->toBeTrue();
});

it('rejects impossible status transitions for partners', function () {
    $booking = Booking::factory()->for($this->traveller)->forPackage($this->package)->completed()->create();

    $this->actingAs($this->partner)
        ->patch("/partners/bookings/{$booking->id}/status", ['status' => 'requested'])
        ->assertSessionHasErrors('status');

    expect($booking->fresh()->status)->toBe(BookingStatus::Completed);
});

it('stops partners from touching another partner booking', function () {
    $other = Business::factory()->for(User::factory()->partner(), 'user')->create(['approved' => true]);
    $otherPackage = Package::factory()->for($other)->create();
    $booking = Booking::factory()->for($this->traveller)->forPackage($otherPackage)->create();

    $this->actingAs($this->partner)
        ->patch("/partners/bookings/{$booking->id}/status", ['status' => 'confirmed'])
        ->assertForbidden();
});

it('lets travellers cancel their own open bookings only', function () {
    $booking = Booking::factory()->for($this->traveller)->forPackage($this->package)->create();

    $this->actingAs($this->traveller)
        ->post("/bookings/{$booking->id}/cancel")
        ->assertRedirect();

    expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled);

    Notification::assertSentTo($this->partner, ActivityNotification::class, function ($notification) use ($booking) {
        return str_contains($notification->payload['title'], 'cancelled')
            && $notification->payload['booking_id'] === $booking->id;
    });

    $completed = Booking::factory()->for($this->traveller)->forPackage($this->package)->completed()->create();

    $this->actingAs($this->traveller)
        ->post("/bookings/{$completed->id}/cancel")
        ->assertSessionHasErrors('status');

    expect($completed->fresh()->status)->toBe(BookingStatus::Completed);
});

it('blocks travellers from opening someone else booking', function () {
    $booking = Booking::factory()->forPackage($this->package)->create();

    $this->actingAs($this->traveller)->get("/account/bookings/{$booking->id}")->assertForbidden();
});
