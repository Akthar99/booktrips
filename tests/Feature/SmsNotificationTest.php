<?php

use App\Enums\BookingStatus;
use App\Jobs\SendSms;
use App\Mail\BookingRequestedMail;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Package;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

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

it('SMS the host when a traveller cancels a booking', function () {
    $partner = User::factory()->partner()->create();
    $business = Business::factory()->for($partner, 'user')->create(['approved' => true, 'phone' => '0771234567']);
    $package = Package::factory()->for($business)->create();

    $traveller = User::factory()->create();

    $booking = app(BookingService::class)->create($traveller, $package, bookingSmsPayload());

    app(BookingService::class)->cancelByGuest($booking, $traveller);

    Http::assertSent(fn ($request) => $request->url() === 'https://app.text.lk/api/v3/sms/send'
        && $request['recipient'] === '94771234567'
        && str_contains((string) $request['message'], 'cancelled booking '.$booking->booking_code));
});

it('SMS the traveller when the host confirms their request', function () {
    $partner = User::factory()->partner()->create();
    $business = Business::factory()->for($partner, 'user')->create(['approved' => true, 'phone' => '0771234567']);
    $package = Package::factory()->for($business)->create();

    $traveller = User::factory()->create();

    $booking = app(BookingService::class)->create($traveller, $package, bookingSmsPayload());

    app(BookingService::class)->updateStatus($booking, BookingStatus::Confirmed, $partner);

    Http::assertSent(fn ($request) => $request->url() === 'https://app.text.lk/api/v3/sms/send'
        && $request['recipient'] === '94771112222'
        && str_contains((string) $request['message'], 'is confirmed'));
});

it('SMS the reported party when a report is opened', function () {
    $partner = User::factory()->partner()->create();
    $business = Business::factory()->for($partner, 'user')->create(['approved' => true, 'phone' => '0771234567']);
    $package = Package::factory()->for($business)->create();
    $traveller = User::factory()->create(['phone' => '0779998888']);

    $booking = Booking::factory()
        ->for($traveller)
        ->forPackage($package)
        ->create([
            'status' => BookingStatus::Confirmed,
            'check_in' => now()->subDays(3)->toDateString(),
            'check_out' => now()->subDays(2)->toDateString(),
        ]);

    $this->actingAs($partner)
        ->post("/bookings/{$booking->id}/disputes", [
            'type' => 'no_show',
            'summary' => 'Guest never arrived',
            'details' => 'We waited two hours.',
        ])
        ->assertRedirect();

    Http::assertSent(fn ($request) => $request->url() === 'https://app.text.lk/api/v3/sms/send'
        && $request['recipient'] === '94779998888'
        && str_contains((string) $request['message'], 'report'));
});

it('never makes the request wait for mail or SMS — both are queued', function () {
    Queue::fake();

    $partner = User::factory()->partner()->create();
    $business = Business::factory()->for($partner, 'user')->create(['approved' => true, 'phone' => '0771234567']);
    $package = Package::factory()->for($business)->create();

    $traveller = User::factory()->create();

    app(BookingService::class)->create($traveller, $package, bookingSmsPayload());

    Queue::assertPushed(SendSms::class, fn (SendSms $job): bool => $job->recipient === '94771234567');
    Mail::assertQueued(BookingRequestedMail::class);
});

/**
 * @return array{check_in: string, check_out: string, guests: int, guest_name: string, guest_phone: string}
 */
function bookingSmsPayload(): array
{
    return [
        'check_in' => now()->addWeek()->toDateString(),
        'check_out' => now()->addWeek()->toDateString(),
        'guests' => 2,
        'guest_name' => 'Kasun Perera',
        'guest_phone' => '0771112222',
    ];
}
