<?php

it('renders the privacy policy page', function () {
    $this->get('/privacy')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('legal/privacy'));
});

it('renders the terms of service page', function () {
    $this->get('/terms')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('legal/terms'));
});
