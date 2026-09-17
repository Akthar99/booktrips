<?php

use App\Enums\BookingStatus;
use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\SupportTicket;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('keeps the operations dashboard off limits to everyone else', function () {
    $this->actingAs(User::factory()->create())->get('/admin')->assertNotFound();
    $this->actingAs(User::factory()->create())->get('/admin/payments')->assertNotFound();
});

it('gives admins insights, money and attention lists', function () {
    $partner = User::factory()->partner()->create();
    $business = Business::factory()->for($partner, 'user')->create(['approved' => true]);
    $package = Package::factory()->for($business)->create();

    Booking::factory()->for(User::factory())->forPackage($package)->create([
        'status' => BookingStatus::Completed,
        'base_total_lkr' => 20000,
        'total_lkr' => 20000,
        'commission_added' => true,
        'commission_lkr' => 2000,
    ]);

    Invoice::factory()->for($business)->create(['period' => '2030-01', 'amount_lkr' => 2000]);

    $this->actingAs($this->admin)
        ->get('/admin')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/overview')
            ->has('finance')
            ->where('finance.billed_lkr', 2000)
            ->has('thisMonth')
            ->has('attention')
            ->has('months', 6)
            ->has('topPartners', 1)
            ->where('topPartners.0.completed_gmv_lkr', 20000)
            ->where('topPartners.0.invoiced_lkr', 2000));
});

it('shows the finance summary with the invoices', function () {
    $partner = User::factory()->partner()->create();
    $business = Business::factory()->for($partner, 'user')->create(['approved' => true]);

    Invoice::factory()->for($business)->create(['period' => '2030-01', 'amount_lkr' => 4000]);
    Invoice::factory()->for($business)->paid()->create(['period' => '2030-02', 'amount_lkr' => 6000]);

    $this->actingAs($this->admin)
        ->get('/admin/payments')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/payments')
            ->where('summary.billed_lkr', 10000)
            ->where('summary.collected_lkr', 6000)
            ->where('summary.outstanding_lkr', 4000));
});

it('gives one partner a full history page', function () {
    $partner = User::factory()->partner()->create();
    $business = Business::factory()->for($partner, 'user')->create(['approved' => true]);
    $package = Package::factory()->for($business)->create();

    Booking::factory()->for(User::factory())->forPackage($package)->completed()->create([
        'base_total_lkr' => 30000,
        'total_lkr' => 30000,
        'commission_added' => true,
        'commission_lkr' => 3000,
    ]);

    // Two threads and two travellers: the history lists must not lazy load anything.
    foreach (['Refund', 'Invoice'] as $subject) {
        SupportTicket::create([
            'user_id' => $partner->id,
            'subject' => $subject,
            'category' => TicketCategory::Payment,
            'status' => TicketStatus::AwaitingAdmin,
            'last_message_at' => now(),
        ]);
    }

    foreach (['Ayesha', 'Nimal'] as $name) {
        Booking::factory()
            ->for(User::factory()->create(['name' => $name]))
            ->forPackage($package)
            ->confirmed()
            ->create();
    }

    $this->actingAs($this->admin)
        ->get("/admin/businesses/{$business->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/businesses/show')
            ->where('business.name', $business->name)
            ->where('business.strikes', 0)
            ->where('totals.bookings', 3)
            ->where('totals.gmv_lkr', 30000 + ($package->price_lkr * 2))
            ->has('bookings', 3)
            ->has('invoices')
            ->has('disputes')
            ->has('tickets', 2));
});

it('gives one traveller a full history page', function () {
    $traveller = User::factory()->create(['strikes' => 1]);
    $partner = User::factory()->partner()->create();
    $business = Business::factory()->for($partner, 'user')->create(['approved' => true]);
    $package = Package::factory()->for($business)->create();

    // Two bookings so the strict-mode lazy loading guard applies to the hydrated models.
    Booking::factory()->for($traveller)->forPackage($package)->completed()->create([
        'base_total_lkr' => 15000,
        'total_lkr' => 15000,
    ]);

    Booking::factory()->for($traveller)->forPackage($package)->confirmed()->create([
        'base_total_lkr' => 5000,
        'total_lkr' => 5000,
    ]);

    foreach (['Refund question', 'Late receipt'] as $subject) {
        SupportTicket::create([
            'user_id' => $traveller->id,
            'subject' => $subject,
            'category' => TicketCategory::Payment,
            'status' => TicketStatus::AwaitingAdmin,
            'last_message_at' => now(),
        ]);
    }

    $this->actingAs($this->admin)
        ->get("/admin/travellers/{$traveller->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/travellers/show')
            ->where('traveller.strikes', 1)
            ->where('totals.bookings', 2)
            ->where('totals.spend_lkr', 20000)
            ->has('bookings', 2)
            ->has('disputes')
            ->has('tickets', 2));
});
