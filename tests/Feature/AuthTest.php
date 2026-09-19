<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('registers a traveller, signs them in and asks for verification', function () {
    Notification::fake();

    $response = $this->post('/register', [
        'name' => 'Nimal Perera',
        'email' => 'NIMAL@BookTrips.lk',
        'phone' => '0771234567',
        'password' => 'ExploreLK123!',
        'password_confirmation' => 'ExploreLK123!',
        'terms' => true,
    ]);

    $response->assertRedirect(route('verification.notice'));

    $user = User::query()->where('email', 'nimal@booktrips.lk')->firstOrFail();

    expect($user->name)->toBe('Nimal Perera')
        ->and($user->role)->toBe(UserRole::User)
        ->and($user->hasVerifiedEmail())->toBeFalse();

    $this->assertAuthenticatedAs($user);

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('never lets a visitor choose the role during registration', function () {
    $this->post('/register', [
        'name' => 'Sneaky User',
        'email' => 'sneaky@example.com',
        'password' => 'ExploreLK123!',
        'password_confirmation' => 'ExploreLK123!',
        'terms' => true,
        'role' => 'admin',
        'active' => true,
    ]);

    expect(User::query()->where('email', 'sneaky@example.com')->firstOrFail()->role)->toBe(UserRole::User);
});

it('refuses a registration that does not accept the terms and privacy policy', function () {
    $this->post('/register', [
        'name' => 'No Terms',
        'email' => 'noterms@example.com',
        'password' => 'ExploreLK123!',
        'password_confirmation' => 'ExploreLK123!',
    ])->assertSessionHasErrors('terms');

    expect(User::query()->where('email', 'noterms@example.com')->exists())->toBeFalse();
});

it('rejects weak passwords and duplicate emails', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $weak = $this->post('/register', [
        'name' => 'Weak Pass',
        'email' => 'weak@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => true,
    ]);

    $weak->assertSessionHasErrors('password');

    $duplicate = $this->post('/register', [
        'name' => 'Duplicate',
        'email' => 'taken@example.com',
        'password' => 'ExploreLK123!',
        'password_confirmation' => 'ExploreLK123!',
        'terms' => true,
    ]);

    $duplicate->assertSessionHasErrors('email');
});

it('logs a verified traveller in and out', function () {
    $user = User::factory()->create(['password' => 'ExploreLK123!']);

    $this->post('/login', ['email' => $user->email, 'password' => 'ExploreLK123!'])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user);

    $this->post('/logout')->assertRedirect('/');
    $this->assertGuest();
});

it('blocks suspended accounts at login', function () {
    $user = User::factory()->suspended()->create(['password' => 'ExploreLK123!']);

    $this->post('/login', ['email' => $user->email, 'password' => 'ExploreLK123!'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('verifies an email through the signed link', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->actingAs($user)->get($url)->assertRedirect('/');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects an email verification link with a bad signature', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get("/verify-email/{$user->id}/".sha1('wrong@example.com'))
        ->assertForbidden();
});

it('emails a reset link and lets the password be changed', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email])
        ->assertSessionHasNoErrors();

    $token = null;

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'BrandNewLK123!',
        'password_confirmation' => 'BrandNewLK123!',
    ])->assertRedirect(route('login'));

    $this->post('/login', ['email' => $user->email, 'password' => 'BrandNewLK123!']);
    $this->assertAuthenticatedAs($user);
});

it('confirms an email change from the signed link', function () {
    Notification::fake();

    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($other);

    $response = $this->post('/account/email', ['email' => 'new-address@example.com']);
    $response->assertSessionHasNoErrors();

    expect($user->fresh()->pending_email)->toBeNull()
        ->and($other->fresh()->pending_email)->toBe('new-address@example.com');

    $url = URL::temporarySignedRoute('email.change.confirm', now()->addHours(24), [
        'user' => $other->id,
    ]);

    $this->get($url)->assertRedirect(route('account.index'));

    $fresh = $other->fresh();

    expect($fresh->email)->toBe('new-address@example.com')
        ->and($fresh->hasVerifiedEmail())->toBeTrue()
        ->and($fresh->pending_email)->toBeNull();
});
