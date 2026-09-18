<?php

use App\Models\Business;
use App\Models\Package;
use App\Models\User;

beforeEach(function () {
    $this->business = Business::factory()->for(User::factory()->partner(), 'user')->create([
        'approved' => true,
    ]);
});

it('renders unique seo metadata for an active package', function () {
    Package::factory()->for($this->business)->create([
        'title' => 'Ella Rock Sunrise Hike',
        'slug' => 'ella-rock-sunrise-hike',
        'category' => 'hiking',
        'location' => 'Ella',
        'district' => 'Badulla',
        'highlight' => 'Climb through tea country with certified local guides',
        'active' => true,
    ]);

    $this->get('/packages/ella-rock-sunrise-hike')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('packages/show')
            ->where('seo.title', 'Ella Rock Sunrise Hike in Ella | Book Trips Sri Lanka')
            ->where('seo.canonical', url('/packages/ella-rock-sunrise-hike'))
            ->has('seo.description'));
});

it('builds a keyword-rich meta description capped at 158 characters', function () {
    Package::factory()->for($this->business)->create([
        'title' => 'Bentota Day Out',
        'slug' => 'bentota-day-out',
        'category' => 'dayout',
        'location' => 'Bentota',
        'district' => 'Galle',
        'highlight' => 'River safari, turtle hatchery and beach time',
        'price_lkr' => 15000,
    ]);

    /** @var array{props: array{seo: array{description: string}}} $page */
    $page = $this->get('/packages/bentota-day-out')->viewData('page');
    $description = $page['props']['seo']['description'];

    expect($description)
        ->toContain('Bentota')
        ->toContain('day out')
        ->toContain('LKR 15,000')
        ->and(mb_strlen($description))->toBeLessThanOrEqual(158);
});

it('avoids repeating the location when the title already contains it', function () {
    Package::factory()->for($this->business)->create([
        'title' => 'Hiking in Ella',
        'slug' => 'hiking-in-ella',
        'location' => 'Ella',
    ]);

    $this->get('/packages/hiking-in-ella')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('seo.title', 'Hiking in Ella | Book Trips Sri Lanka'));
});
