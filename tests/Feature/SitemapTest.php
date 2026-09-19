<?php

use App\Models\Business;
use App\Models\Package;
use App\Models\User;

beforeEach(function () {
    @unlink(public_path('sitemap.xml'));
});

afterEach(function () {
    @unlink(public_path('sitemap.xml'));
});

it('writes a sitemap with every active package and no inactive ones', function () {
    $business = Business::factory()->for(User::factory()->partner(), 'user')->create(['approved' => true]);

    Package::factory()->for($business)->create(['slug' => 'hiking-in-ella', 'active' => true]);
    Package::factory()->for($business)->create(['slug' => 'hidden-trip', 'active' => false]);

    $this->artisan('booktrips:sitemap:generate')->assertSuccessful();

    $xml = (string) file_get_contents(public_path('sitemap.xml'));

    expect($xml)
        ->toContain('/packages/hiking-in-ella')
        ->toContain('/search')
        ->toContain('/privacy')
        ->toContain('/terms')
        ->not->toContain('/packages/hidden-trip')
        ->and(simplexml_load_string($xml))->not->toBeFalse();
});
