<?php

use App\Models\Booking;
use App\Models\Business;
use App\Models\Package;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

    config([
        'booktrips.sms.api_key' => 'test-key',
        'booktrips.sms.sender_id' => 'BookTrips',
    ]);

    Http::fake(fn () => Http::response(['status' => 'success']));
});

it('SMS the host when a traveller requests a booking', function () {
    $partner = User::factory()->partner()->create();
    $business = Business::factory()->for($partner, 'user')->create(['approved' => true, 'phone' => '0771234567']);
    $package = Package::factory()->for($business)->create();

    $traveller = User::factory()->create();

    app(BookingService::class)->create($traveller, $package, [
        'check_in' => now()->addWeek()->toDateString(),
        'check_out' => now()->addWeek()->toDateString(),
        'guests' => 2,
        'guest_name' => 'Kasun Perera',
        'guest_phone' => '0771112222',
    ]);

    $booking = Booking::query()->firstOrFail();

    Http::assertSent(fn ($request) => $request->url() === 'https://app.text.lk/api/v3/sms/send'
        && $request['recipient'] === '94771234567'
        && str_contains((string) $request['message'], $booking->booking_code)
        && str_contains((string) $request['message'], 'New BookTrips request'));
});

it('SMS the partner when their application is first approved', function () {
    $partner = User::factory()->partner()->create();
    $business = Business::factory()->for($partner, 'user')->create(['approved' => false, 'phone' => '0765551234']);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/admin/partners/{$business->id}/approve", ['approved' => true])
        ->assertRedirect();

    Http::assertSent(fn ($request) => $request->url() === 'https://app.text.lk/api/v3/sms/send'
        && $request['recipient'] === '94765551234'
        && str_contains((string) $request['message'], 'approved'));
});
