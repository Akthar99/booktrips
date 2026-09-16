<?php

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\ReceiptStatus;
use App\Mail\PartnerApprovedMail;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Receipt;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->admin = User::factory()->admin()->create();
    $this->traveller = User::factory()->create();
    $this->partner = User::factory()->partner()->create();
    $this->business = Business::factory()->for($this->partner, 'user')->create(['approved' => false]);
});

it('protects the admin console without advertising it', function () {
    $this->get('/admin')->assertRedirect(route('login'));

    // Non-admins get a plain 404 so the console stays invisible.
    $this->actingAs($this->traveller)->get('/admin')->assertNotFound();
    $this->actingAs($this->traveller)->get('/admin/users')->assertNotFound();

    $this->actingAs(User::factory()->partner()->create())->get('/admin')->assertNotFound();
});

it('approves and revokes partners', function () {
    Notification::fake();
    Mail::fake();

    $this->actingAs($this->admin)
        ->patch("/admin/partners/{$this->business->id}/approve", ['approved' => true])
        ->assertRedirect();

    expect($this->business->fresh()->approved)->toBeTrue();

    Mail::assertQueued(PartnerApprovedMail::class, fn (PartnerApprovedMail $mail): bool => $mail->business->id === $this->business->id);

    $this->actingAs($this->admin)
        ->patch("/admin/partners/{$this->business->id}/approve", ['approved' => false]);

    expect($this->business->fresh()->approved)->toBeFalse();

    // Revoking never congratulates anyone, and re-approving sends nothing new.
    Mail::assertQueued(PartnerApprovedMail::class, 1);
});

it('lists every application detail an admin needs to decide', function () {
    $this->business->forceFill([
        'description' => 'Family run camp with four tents.',
        'address' => 'Passara Road',
        'phone' => '0771234567',
        'website' => 'https://ridge.example',
        'instagram' => 'https://instagram.com/ridge',
        'whatsapp' => '94771234567',
        'phone_verified_at' => now(),
    ])->save();

    $this->actingAs($this->admin)
        ->get('/admin/partners')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/partners')
            ->where('businesses.data.0.description', 'Family run camp with four tents.')
            ->where('businesses.data.0.address', 'Passara Road')
            ->where('businesses.data.0.website', 'https://ridge.example')
            ->where('businesses.data.0.whatsapp', '94771234567')
            ->where('businesses.data.0.phone_verified_at', fn ($value) => $value !== null)
            ->where('businesses.data.0.owner.email', $this->partner->email));
});

it('lists, features and unlists packages', function () {
    $package = Package::factory()->for($this->business)->create(['active' => true, 'featured' => false]);

    $this->actingAs($this->admin)
        ->patch("/admin/packages/{$package->id}", ['featured' => true]);

    expect($package->fresh()->featured)->toBeTrue();

    $this->actingAs($this->admin)
        ->patch("/admin/packages/{$package->id}", ['active' => false]);

    expect($package->fresh()->active)->toBeFalse();
});

it('suspends users and confirms verification', function () {
    $this->actingAs($this->admin)
        ->patch("/admin/users/{$this->traveller->id}", ['active' => false]);

    expect($this->traveller->fresh()->active)->toBeFalse();

    $this->post('/logout');

    $this->post('/login', ['email' => $this->traveller->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $unverified = User::factory()->unverified()->create();

    $this->actingAs($this->admin)
        ->patch("/admin/users/{$unverified->id}", ['email_verified' => true]);

    expect($unverified->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('will not let an admin change their own account or another admin', function () {
    $this->actingAs($this->admin)
        ->patch("/admin/users/{$this->admin->id}", ['active' => false])
        ->assertSessionHasErrors('user');

    $otherAdmin = User::factory()->admin()->create();

    $this->actingAs($this->admin)
        ->patch("/admin/users/{$otherAdmin->id}", ['active' => false])
        ->assertSessionHasErrors('user');
});

it('confirms a receipt to mark the invoice paid', function () {
    $invoice = Invoice::factory()->for($this->business)->create([
        'period' => '2030-04',
        'status' => InvoiceStatus::Submitted,
    ]);

    $receipt = Receipt::factory()->for($invoice, 'invoice')->create([
        'business_id' => $this->business->id,
        'status' => ReceiptStatus::Pending,
    ]);

    $this->actingAs($this->admin)
        ->patch("/admin/receipts/{$receipt->id}", ['status' => 'confirmed'])
        ->assertRedirect();

    expect($receipt->fresh()->status)->toBe(ReceiptStatus::Confirmed)
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->fresh()->paid_at)->not->toBeNull();

    Notification::assertSentTo($this->partner, ActivityNotification::class, function ($notification) {
        return str_contains($notification->payload['title'], 'confirmed')
            && $notification->payload['link'] === '/partners/payments';
    });
});

it('reopens the invoice when a receipt is rejected', function () {
    $invoice = Invoice::factory()->for($this->business)->create([
        'period' => '2030-05',
        'status' => InvoiceStatus::Submitted,
    ]);

    $receipt = Receipt::factory()->for($invoice, 'invoice')->create([
        'business_id' => $this->business->id,
        'status' => ReceiptStatus::Pending,
    ]);

    $this->actingAs($this->admin)
        ->patch("/admin/receipts/{$receipt->id}", ['status' => 'rejected']);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Open);

    Notification::assertSentTo($this->partner, ActivityNotification::class, function ($notification) {
        return $notification->payload['title'] === 'Receipt needs another look';
    });
});

it('lets an admin finish any booking and raise commission', function () {
    $package = Package::factory()->for($this->business)->create(['price_lkr' => 30000]);
    $booking = Booking::factory()->for($this->traveller)->forPackage($package)->confirmed()->create([
        'base_total_lkr' => 30000,
        'total_lkr' => 30000,
    ]);

    $this->actingAs($this->admin)
        ->patch("/admin/bookings/{$booking->id}/status", ['status' => 'completed'])
        ->assertRedirect();

    expect($booking->fresh()->status)->toBe(BookingStatus::Completed)
        ->and($booking->fresh()->commission_lkr)->toBe(3000)
        ->and(Invoice::query()->where('business_id', $this->business->id)->exists())->toBeTrue();
});
