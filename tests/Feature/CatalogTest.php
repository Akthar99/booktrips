<?php

use App\Models\Business;
use App\Models\Package;
use App\Models\User;

beforeEach(function () {
    $this->business = Business::factory()->for(User::factory()->partner(), 'user')->create([
        'district' => 'Badulla',
        'city' => 'Ella',
    ]);

    $this->package = Package::factory()->for($this->business)->create([
        'title' => 'Ella Gap camping night',
        'slug' => 'ella-gap-camping-night',
        'category' => 'camping',
        'location' => 'Ella',
        'district' => 'Badulla',
        'price_lkr' => 12500,
        'price_type' => 'per_person',
        'max_guests' => 12,
        'featured' => true,
    ]);
});

it('shows the home page with featured packages', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('home')
            ->has('featured', 1)
            ->has('categories')
            ->has('pins'));
});

it('filters the catalogue by category, guests and price', function () {
    Package::factory()->for($this->business)->create([
        'category' => 'hotels',
        'price_lkr' => 90000,
        'max_guests' => 2,
    ]);

    $this->get('/search?category=camping')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('search')->has('packages', 1));

    $this->get('/search?guests=10')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('packages', 1));

    $this->get('/search?maxPrice=20000')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('packages', 1));

    $this->get('/search?q=leafy')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('packages', 0));
});

it('never exposes hidden packages', function () {
    $hidden = Package::factory()->for($this->business)->inactive()->create(['slug' => 'hidden-package']);

    $this->get('/search')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('packages', 1));

    $this->get('/packages/hidden-package')->assertNotFound();
    $this->get("/packages/{$hidden->id}")->assertNotFound();
});

it('opens a package by slug or id', function () {
    $this->get('/packages/ella-gap-camping-night')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('packages/show')
            ->where('package.title', 'Ella Gap camping night')
            ->has('reviews'));

    $this->get("/packages/{$this->package->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('packages/show'));
});

it('quotes a stay server-side and applies discounts', function () {
    $discounted = Package::factory()->for($this->business)->discounted(20)->create([
        'slug' => 'discounted-package',
        'price_lkr' => 10000,
        'price_type' => 'per_person',
        'max_guests' => 6,
        'min_guests' => 1,
    ]);

    $response = $this->getJson("/packages/{$discounted->id}/quote?check_in=2030-01-10&check_out=2030-01-10&guests=2");

    $response->assertOk()
        ->assertJson([
            'base_total_lkr' => 20000,
            'discount_lkr' => 4000,
            'total_lkr' => 16000,
            'payment_method' => 'pay_at_destination',
        ]);
});

it('rejects quotes outside the guest range', function () {
    $this->getJson("/packages/{$this->package->id}/quote?check_in=2030-01-10&check_out=2030-01-10&guests=40")
        ->assertStatus(422)
        ->assertJsonValidationErrors('guests');
});

it('rejects quotes outside the running days', function () {
    $this->package->forceFill([
        'weekdays' => [6],
    ])->save();

    // 2030-01-10 is a Thursday.
    $this->getJson("/packages/{$this->package->id}/quote?check_in=2030-01-10&check_out=2030-01-10&guests=2")
        ->assertStatus(422)
        ->assertJsonValidationErrors('check_in');
});

it('renders the map with pins', function () {
    $this->get('/map')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('map')->has('packages', 1));
});

it('proxies geo search with a minimum query length', function () {
    $this->getJson('/geo/search?q=e')->assertOk()->assertJson(['results' => []]);
});
