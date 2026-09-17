<?php

use App\Models\Booking;
use App\Models\Business;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Performance guards.
 *
 * The site felt slow because pages loaded whole tables into PHP and fired a query per
 * row (or per stat card). These tests pin the query count of the busiest pages, so a
 * regression shows up in the suite instead of in production.
 */
function queriesWhile(callable $callback): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $callback();

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

it('paginates the catalogue in the database instead of loading every listing', function () {
    $business = Business::factory()->for(User::factory()->partner()->create(), 'user')->create(['approved' => true]);
    Package::factory()->for($business)->count(30)->create();

    $this->get('/search')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('search')
            ->has('packages', 12)
            ->where('total', 30)
            ->where('pagination.last_page', 3)
            ->where('pagination.page', 1));

    $this->get('/search?page=3')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('packages', 6)
            ->where('pagination.page', 3));
});

it('filters the catalogue by category, guests and discounted price in SQL', function () {
    $business = Business::factory()->for(User::factory()->partner()->create(), 'user')->create(['approved' => true]);

    Package::factory()->for($business)->create([
        'title' => 'Cheap surfing camp',
        'category' => 'surfing',
        'price_lkr' => 20000,
        'max_guests' => 4,
        'discount_enabled' => true,
        'discount_type' => 'percentage',
        'discount_value' => 50,
        'rating' => 4.5,
    ]);

    Package::factory()->for($business)->create([
        'title' => 'Pricey safari lodge',
        'category' => 'wildlife',
        'price_lkr' => 40000,
        'max_guests' => 8,
        'discount_enabled' => false,
        'rating' => 4.9,
    ]);

    // 20,000 with 50% off = 10,000, so a max price of 15,000 keeps only the discounted one.
    $this->get('/search?maxPrice=15000')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('packages', 1)
            ->where('packages.0.title', 'Cheap surfing camp'));

    $this->get('/search?category=wildlife&guests=6')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('packages', 1)
            ->where('packages.0.title', 'Pricey safari lodge'));

    $this->get('/search?sort=price_asc')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('packages.0.title', 'Cheap surfing camp'));
});

it('keeps the catalogue within a fixed query budget as listings grow', function () {
    $business = Business::factory()->for(User::factory()->partner()->create(), 'user')->create(['approved' => true]);
    Package::factory()->for($business)->count(40)->create();

    $queries = queriesWhile(fn () => $this->get('/search')->assertOk());

    // Measured: 3. Listing count and page size must not change this number.
    expect($queries)->toBeLessThanOrEqual(6);
});

it('keeps the admin user list flat instead of counting bookings per row', function () {
    User::factory()->count(25)->create();

    $admin = User::factory()->admin()->create();

    $queries = queriesWhile(fn () => $this->actingAs($admin)->get('/admin/users')->assertOk());

    // One page of 20 users must not turn into 20 extra count queries. Measured: 6.
    expect($queries)->toBeLessThanOrEqual(8);
});

it('keeps the admin overview to a handful of aggregate queries', function () {
    $partner = User::factory()->partner()->create();
    $business = Business::factory()->for($partner, 'user')->create(['approved' => true]);
    $package = Package::factory()->for($business)->create();

    Booking::factory()->for(User::factory())->forPackage($package)->count(5)->create();
    Booking::factory()->for(User::factory())->forPackage($package)->confirmed()->create();
    Booking::factory()->for(User::factory())->forPackage($package)->completed()->create();

    $admin = User::factory()->admin()->create();

    $queries = queriesWhile(fn () => $this->actingAs($admin)->get('/admin')->assertOk());

    // Used to be ~50 count/sum queries; the six-month chart alone was 18. Measured: 15.
    expect($queries)->toBeLessThanOrEqual(18);
});

it('keeps the partner dashboard to one stats query', function () {
    $partner = User::factory()->partner()->create();
    $business = Business::factory()->for($partner, 'user')->create(['approved' => true]);
    $package = Package::factory()->for($business)->create(['created_at' => now()->subDay()]);
    Package::factory()->for($business)->create(['created_at' => now()->subDays(2)]);

    Booking::factory()->forPackage($package)->confirmed()->create([
        'check_in' => now()->addWeek()->toDateString(),
        'check_out' => now()->addWeek()->toDateString(),
    ]);

    $queries = queriesWhile(fn () => $this->actingAs($partner)->get('/partners/dashboard')->assertOk());

    // Measured: 6 — one for the packages, one aggregate for the stats.
    expect($queries)->toBeLessThanOrEqual(8);
});

it('keeps the traveller booking list within budget', function () {
    $traveller = User::factory()->create();
    $partner = User::factory()->partner()->create();
    $business = Business::factory()->for($partner, 'user')->create(['approved' => true]);
    $package = Package::factory()->for($business)->create();

    Booking::factory()->for($traveller)->forPackage($package)->count(8)->create();

    $queries = queriesWhile(fn () => $this->actingAs($traveller)->get('/account/bookings')->assertOk());

    expect($queries)->toBeLessThanOrEqual(12);
});
