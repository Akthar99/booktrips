<?php

use App\Enums\TicketStatus;
use App\Mail\SupportReplyMail;
use App\Models\Booking;
use App\Models\Business;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Mail::fake();
    Notification::fake();

    $this->admin = User::factory()->admin()->create();
    $this->traveller = User::factory()->create();
});

it('opens a thread and tells the admins', function () {
    $this->actingAs($this->traveller)
        ->post('/support', [
            'subject' => 'Refund question',
            'category' => 'payment',
            'body' => 'The host asked for extra money at the gate — what should I do?',
        ])
        ->assertRedirect();

    $ticket = SupportTicket::query()->firstOrFail();

    expect($ticket->status)->toBe(TicketStatus::AwaitingAdmin)
        ->and($ticket->messages)->toHaveCount(1);

    Notification::assertSentTo($this->admin, ActivityNotification::class, function ($notification) use ($ticket) {
        return $notification->payload['link'] === '/admin/support?ticket='.$ticket->id;
    });
});

it('hands the thread back and forth', function () {
    $this->actingAs($this->traveller)->post('/support', [
        'subject' => 'Refund question',
        'category' => 'payment',
        'body' => 'The host asked for extra money at the gate — what should I do?',
    ]);

    $ticket = SupportTicket::query()->firstOrFail();

    // Staff reply → the traveller is told and the thread waits on them.
    $this->actingAs($this->admin)
        ->post("/admin/support/{$ticket->id}/reply", ['body' => 'Thanks — send us the name of the driver and we will look into it.'])
        ->assertRedirect();

    expect($ticket->fresh()->status)->toBe(TicketStatus::AwaitingUser);

    Notification::assertSentTo($this->traveller, ActivityNotification::class);
    Mail::assertQueued(SupportReplyMail::class);

    // User replies → it is back with the admins.
    $this->actingAs($this->traveller)
        ->post("/support/{$ticket->id}/reply", ['body' => 'It was Sunil, pickup was at 6am in Ella.'])
        ->assertRedirect();

    expect($ticket->fresh()->status)->toBe(TicketStatus::AwaitingAdmin)
        ->and($ticket->fresh()->messages)->toHaveCount(3);
});

it('keeps other people out of a thread', function () {
    $other = User::factory()->create();

    $this->actingAs($this->traveller)->post('/support', [
        'subject' => 'Private thing',
        'category' => 'account',
        'body' => 'Something only I should see in this thread, please.',
    ]);

    $ticket = SupportTicket::query()->firstOrFail();

    $this->actingAs($other)->post("/support/{$ticket->id}/reply", ['body' => 'Let me in!'])
        ->assertNotFound();

    $response = $this->actingAs($other)->get('/support?ticket='.$ticket->id);

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->where('selected', null));
});

it('stops replies on a resolved thread until it is reopened', function () {
    $this->actingAs($this->traveller)->post('/support', [
        'subject' => 'Done deal',
        'category' => 'other',
        'body' => 'Everything is sorted now, thanks for the help team.',
    ]);

    $ticket = SupportTicket::query()->firstOrFail();

    $this->actingAs($this->admin)
        ->patch("/admin/support/{$ticket->id}", ['status' => 'resolved'])
        ->assertRedirect();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Resolved);

    $this->actingAs($this->traveller)
        ->post("/support/{$ticket->id}/reply", ['body' => 'One more thing actually…'])
        ->assertSessionHasErrors('body');

    $this->actingAs($this->traveller)
        ->patch("/support/{$ticket->id}", ['status' => 'open'])
        ->assertRedirect();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Open);

    $this->actingAs($this->traveller)
        ->post("/support/{$ticket->id}/reply", ['body' => 'One more thing actually…'])
        ->assertRedirect();
});

it('only links bookings that belong to the user', function () {
    $partner = User::factory()->partner()->create();
    Business::factory()->for($partner, 'user')->create(['approved' => true]);
    $booking = Booking::factory()->for(User::factory())->create();

    $this->actingAs($this->traveller)
        ->post('/support', [
            'subject' => 'Not my booking',
            'category' => 'booking',
            'body' => 'Attaching somebody else booking to my request here.',
            'booking_id' => $booking->id,
        ])
        ->assertSessionHasErrors('booking_id');
});

it('lists support threads for admins only', function () {
    $this->actingAs($this->traveller)->post('/support', [
        'subject' => 'Needs an answer',
        'category' => 'booking',
        'body' => 'Could someone check my booking dates for me please?',
    ]);

    $this->actingAs($this->traveller)->get('/admin/support')->assertNotFound();

    $this->actingAs($this->admin)
        ->get('/admin/support')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/support')
            ->has('tickets.data', 1)
            ->where('counts.awaiting_admin', 1));
});

it('renders the traveller inbox with several threads', function () {
    foreach (['Refund question', 'Change my dates'] as $subject) {
        $this->actingAs($this->traveller)->post('/support', [
            'subject' => $subject,
            'category' => 'booking',
            'body' => 'Please help me with this booking, thank you in advance.',
        ]);
    }

    $this->actingAs($this->traveller)
        ->get('/support')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('support/index')
            ->has('tickets', 2));
});

it('renders one thread with its messages', function () {
    $this->actingAs($this->traveller)->post('/support', [
        'subject' => 'Refund question',
        'category' => 'payment',
        'body' => 'The host asked for extra money at the gate — what should I do?',
    ]);

    $ticket = SupportTicket::query()->firstOrFail();

    $this->actingAs($this->traveller)
        ->get('/support?ticket='.$ticket->id)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('support/index')
            ->where('selected.subject', 'Refund question')
            ->has('selected.messages', 1));
});
