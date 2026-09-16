<?php

use App\Mail\HostBookingNoticeMail;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Package;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Mail::fake();
    Notification::fake();

    $this->admin = User::factory()->admin()->create();
    $this->business = Business::factory()->for(User::factory()->partner(), 'user')->create();
    $this->package = Package::factory()->for($this->business)->create();
});

it('escalates unanswered requests older than the window', function () {
    $stale = Booking::factory()->forPackage($this->package)->stale()->create();
    $fresh = Booking::factory()->forPackage($this->package)->create();

    $this->artisan('booktrips:escalate-stale-bookings')->assertSuccessful();

    expect($stale->fresh()->escalated)->toBeTrue()
        ->and($stale->fresh()->escalated_at)->not->toBeNull()
        ->and($fresh->fresh()->escalated)->toBeFalse();

    Notification::assertSentTo($this->admin, ActivityNotification::class);
    Mail::assertQueued(HostBookingNoticeMail::class);
});

it('does not escalate bookings that were already answered', function () {
    $confirmed = Booking::factory()->forPackage($this->package)->stale()->confirmed()->create();

    $this->artisan('booktrips:escalate-stale-bookings')->assertSuccessful();

    expect($confirmed->fresh()->escalated)->toBeFalse();
});
