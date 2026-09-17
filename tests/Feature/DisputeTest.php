<?php

use App\Enums\BookingStatus;
use App\Enums\DisputeStatus;
use App\Enums\InvoiceStatus;
use App\Mail\DisputeNoticeMail;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Dispute;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Mail::fake();
    Notification::fake();

    $this->admin = User::factory()->admin()->create();
    $this->partner = User::factory()->partner()->create();
    $this->business = Business::factory()->for($this->partner, 'user')->create(['approved' => true]);
    $this->package = Package::factory()->for($this->business)->create();
    $this->traveller = User::factory()->create();

    $this->booking = Booking::factory()
        ->for($this->traveller)
        ->forPackage($this->package)
        ->create([
            'status' => BookingStatus::Confirmed,
            'check_in' => now()->subDays(3)->toDateString(),
            'check_out' => now()->subDays(2)->toDateString(),
        ]);
});

/**
 * @return array{type: string, summary: string, details: string}
 */
function noShowReport(): array
{
    return [
        'type' => 'no_show',
        'summary' => 'Guest never arrived',
        'details' => 'We waited two hours and called twice.',
    ];
}

it('lets a partner report a no-show and lets the traveller answer', function () {
    $this->actingAs($this->partner)
        ->post("/bookings/{$this->booking->id}/disputes", noShowReport())
        ->assertRedirect(route('partner.bookings.show', $this->booking));

    $dispute = Dispute::query()->firstOrFail();

    expect($dispute->status)->toBe(DisputeStatus::AwaitingResponse)
        ->and($dispute->against_user_id)->toBe($this->traveller->id)
        ->and($dispute->raised_by_user_id)->toBe($this->partner->id)
        ->and($dispute->response_deadline_at->isAfter(now()->addHours(40)))->toBeTrue();

    Notification::assertSentTo($this->traveller, ActivityNotification::class);
    Notification::assertSentTo($this->admin, ActivityNotification::class);
    Mail::assertQueued(DisputeNoticeMail::class);

    // The traveller tells their side.
    $this->actingAs($this->traveller)
        ->post("/disputes/{$dispute->id}/respond", [
            'response' => 'I cancelled by phone two days before and the host said it was fine.',
        ])
        ->assertRedirect(route('bookings.show', $this->booking));

    expect($dispute->fresh()->status)->toBe(DisputeStatus::UnderReview)
        ->and($dispute->fresh()->responded_at)->not->toBeNull();

    Notification::assertSentTo($this->admin, ActivityNotification::class);
});

it('lets a traveller report a partner and rejects mismatched report types', function () {
    $this->actingAs($this->traveller)
        ->post("/bookings/{$this->booking->id}/disputes", [
            'type' => 'no_show', // partner-only type
            'summary' => 'Trying the wrong kind of report',
        ])
        ->assertSessionHasErrors('type');

    $this->actingAs($this->traveller)
        ->post("/bookings/{$this->booking->id}/disputes", [
            'type' => 'payment_denied',
            'summary' => 'Host says I did not pay',
            'details' => 'I paid Rs. 20,000 in cash at the gate and have a photo of the receipt.',
        ])
        ->assertRedirect(route('bookings.show', $this->booking));

    $dispute = Dispute::query()->firstOrFail();

    expect($dispute->against_user_id)->toBe($this->partner->id)
        ->and($dispute->business_id)->toBe($this->business->id);
});

it('blocks reports before the trip, on open bookings and duplicates', function () {
    $future = Booking::factory()->for($this->traveller)->forPackage($this->package)->confirmed()->create();

    $this->actingAs($this->partner)
        ->post("/bookings/{$future->id}/disputes", noShowReport())
        ->assertSessionHasErrors('booking');

    $this->actingAs($this->partner)
        ->post("/bookings/{$this->booking->id}/disputes", noShowReport())
        ->assertRedirect();

    $this->actingAs($this->partner)
        ->post("/bookings/{$this->booking->id}/disputes", noShowReport())
        ->assertSessionHasErrors('booking');

    expect(Dispute::query()->count())->toBe(1);
});

it('keeps a stranger out of the report flow', function () {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->post("/bookings/{$this->booking->id}/disputes", [
            'type' => 'other',
            'summary' => 'Not my booking',
        ])
        ->assertForbidden();
});

it('will not let the accused answer their own report', function () {
    $this->actingAs($this->partner)->post("/bookings/{$this->booking->id}/disputes", noShowReport());

    $dispute = Dispute::query()->firstOrFail();

    $this->actingAs($this->partner)
        ->post("/disputes/{$dispute->id}/respond", ['response' => 'I am the one who reported you, actually.'])
        ->assertForbidden();
});

it('waits for the response window before a verdict', function () {
    $this->actingAs($this->partner)->post("/bookings/{$this->booking->id}/disputes", noShowReport());

    $dispute = Dispute::query()->firstOrFail();

    expect($dispute->canBeResolved())->toBeFalse();

    // Admins pass every gate, so the guard is the service rule itself.
    $this->actingAs($this->admin)
        ->patch("/admin/disputes/{$dispute->id}", ['resolution' => 'customer_fault'])
        ->assertSessionHasErrors('resolution');

    expect($dispute->fresh()->status)->toBe(DisputeStatus::AwaitingResponse);

    // After the window closes a verdict is allowed even without a reply.
    $this->travel(49)->hours();

    $this->actingAs($this->admin)
        ->patch("/admin/disputes/{$dispute->id}", [
            'resolution' => 'customer_fault',
            'penalty' => 'strike',
            'resolution_note' => 'Host called twice and the guest never replied.',
        ])
        ->assertRedirect();

    $dispute->refresh();

    expect($dispute->status)->toBe(DisputeStatus::Resolved)
        ->and($dispute->penalty_amount_lkr)->toBeNull()
        ->and($this->traveller->fresh()->strikes)->toBe(1)
        ->and($this->traveller->fresh()->active)->toBeTrue();

    Notification::assertSentTo($this->traveller, ActivityNotification::class);
    Notification::assertSentTo($this->partner, ActivityNotification::class);
});

it('suspends a traveller who reaches three strikes', function () {
    $second = Booking::factory()->for($this->traveller)->forPackage($this->package)->create([
        'status' => BookingStatus::Confirmed,
        'check_in' => now()->subDays(6)->toDateString(),
        'check_out' => now()->subDays(5)->toDateString(),
    ]);

    foreach ([$this->booking, $second] as $index => $booking) {
        $this->actingAs($index === 0 ? $this->partner : $this->partner)
            ->post("/bookings/{$booking->id}/disputes", noShowReport());
    }

    $first = Dispute::query()->oldest('id')->firstOrFail();
    $latest = Dispute::query()->latest('id')->firstOrFail();

    $this->travel(49)->hours();
    $this->actingAs($this->admin)->patch("/admin/disputes/{$first->id}", ['resolution' => 'customer_fault', 'penalty' => 'strike']);

    $this->travelBack();
    $this->travel(49)->hours();
    $this->actingAs($this->admin)->patch("/admin/disputes/{$latest->id}", ['resolution' => 'customer_fault', 'penalty' => 'suspend']);

    $traveller = $this->traveller->fresh();

    expect($traveller->strikes)->toBe(3)
        ->and($traveller->active)->toBeFalse();
});

it('bills a partner who is found at fault and adds a strike', function () {
    $this->actingAs($this->traveller)->post("/bookings/{$this->booking->id}/disputes", [
        'type' => 'payment_denied',
        'summary' => 'Host denies receiving payment',
    ]);

    $dispute = Dispute::query()->firstOrFail();

    $this->actingAs($this->partner)
        ->post("/disputes/{$dispute->id}/respond", ['response' => 'I checked my account, the transfer never arrived.']);

    $this->actingAs($this->admin)
        ->patch("/admin/disputes/{$dispute->id}", [
            'resolution' => 'partner_fault',
            'penalty' => 'strike',
            'penalty_amount_lkr' => 5000,
            'resolution_note' => 'Bank statement from the guest shows a successful transfer.',
        ])
        ->assertRedirect();

    $business = $this->business->fresh();
    $invoice = Invoice::query()->firstOrFail();

    expect($business->strikes)->toBe(1)
        ->and($invoice->amount_lkr)->toBe(5000)
        ->and($invoice->status)->toBe(InvoiceStatus::Open)
        ->and($invoice->lines[0]['penalty'])->toBeTrue();
});

it('shows reports to the admin console only for admins', function () {
    $this->actingAs($this->partner)->post("/bookings/{$this->booking->id}/disputes", noShowReport());

    $this->actingAs($this->traveller)->get('/admin/disputes')->assertNotFound();

    $this->actingAs($this->admin)
        ->get('/admin/disputes')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/disputes')
            ->has('disputes.data', 1)
            ->where('counts.awaiting', 1));
});
