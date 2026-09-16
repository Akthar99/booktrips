<?php

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    config([
        'booktrips.sms.api_key' => 'test-key',
        'booktrips.sms.sender_id' => 'BookTrips',
    ]);

    $this->code = null;

    Http::fake(function ($request) {
        if (preg_match('/code is (\d{6})/', (string) $request['message'], $matches)) {
            $this->code = $matches[1];
        }

        return Http::response(['status' => 'success', 'data' => 'queued']);
    });

    $this->admin = User::factory()->admin()->create();
});

/**
 * Run the OTP round trip the way the apply page does.
 */
function verifyPhone(string $phone = '0771234567'): void
{
    test()->post('/partners/apply/phone', ['phone' => $phone])->assertOk();
    test()->post('/partners/apply/phone/confirm', ['phone' => $phone, 'code' => test()->code])->assertOk();
}

function applicationPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Kasun Silva',
        'email' => 'kasun@example.com',
        'phone' => '0771234567',
        'password' => 'ExploreLK123!',
        'password_confirmation' => 'ExploreLK123!',
        'business_name' => 'Ella Ridge Camp',
        'type' => 'camping_site',
        'description' => 'Tented camp on the ridge with dinner and sunrise.',
        'address' => 'Passara Road',
        'city' => 'Ella',
        'district' => 'Badulla',
        'website' => 'https://ellaridge.example',
    ], $overrides);
}

it('lets a guest apply and notifies the admins straight away', function () {
    $this->get('/partners/apply')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('partners/apply')
            ->where('mode', 'register')
            ->where('account', null)
            ->has('businessTypes'));

    verifyPhone();

    $response = $this->post('/partners/apply', applicationPayload());

    $response->assertRedirect(route('partner.pending'));

    $user = User::query()->where('email', 'kasun@example.com')->firstOrFail();

    expect($user->role)->toBe(UserRole::Business)
        ->and($user->business)->not->toBeNull()
        ->and($user->business->approved)->toBeFalse()
        ->and($user->business->phone_verified_at)->not->toBeNull();

    $this->assertAuthenticatedAs($user);

    Notification::assertSentTo($this->admin, ActivityNotification::class, function ($notification) {
        return $notification->payload['type'] === 'partner_application'
            && $notification->payload['link'] === '/admin/partners'
            && str_contains($notification->payload['title'], 'Ella Ridge Camp');
    });
});

it('refuses an application with an unverified phone number', function () {
    $this->post('/partners/apply', applicationPayload())
        ->assertSessionHasErrors('phone');

    expect(User::query()->where('email', 'kasun@example.com')->exists())->toBeFalse();
});

it('refuses an application for a different number than the verified one', function () {
    verifyPhone('0771234567');

    $this->post('/partners/apply', applicationPayload(['phone' => '0719999999']))
        ->assertSessionHasErrors('phone');

    expect(Business::query()->count())->toBe(0);
});

it('needs at least one social or web profile', function () {
    verifyPhone();

    $this->post('/partners/apply', applicationPayload([
        'website' => null,
        'instagram' => null,
        'facebook' => null,
        'tiktok' => null,
        'whatsapp' => null,
    ]))->assertSessionHasErrors('socials');

    expect(Business::query()->count())->toBe(0);
});

it('accepts a single social profile instead of a website', function () {
    verifyPhone();

    $this->post('/partners/apply', applicationPayload([
        'website' => null,
        'instagram' => 'https://instagram.com/ellaridge',
    ]))->assertRedirect(route('partner.pending'));

    expect(Business::query()->count())->toBe(1);
});

it('tells a guest with an existing account to sign in instead', function () {
    User::factory()->create(['email' => 'kasun@example.com']);

    verifyPhone();

    $this->post('/partners/apply', applicationPayload())
        ->assertSessionHasErrors(['email' => 'This email already has a BookTrips account. Sign in, then use the partner application again to upgrade it.']);

    expect(Business::query()->count())->toBe(0);
});

it('upgrades a signed-in traveller on the same account', function () {
    $traveller = User::factory()->create(['name' => 'Nimal Perera', 'phone' => '0719999999']);

    $this->actingAs($traveller)
        ->get('/partners/apply')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('mode', 'upgrade')
            ->where('account.email', $traveller->email)
            ->where('account.name', 'Nimal Perera'));

    // No email or password is sent: the account already exists.
    verifyPhone('0719999999');

    $this->actingAs($traveller)
        ->post('/partners/apply', [
            'name' => 'Nimal Perera',
            'phone' => '0719999999',
            'business_name' => 'Nimal Surf School',
            'type' => 'activity_provider',
            'description' => 'Lessons for beginners on Weligama beach.',
            'city' => 'Weligama',
            'district' => 'Matara',
            'whatsapp' => '94719999999',
        ])
        ->assertRedirect(route('partner.pending'));

    expect(User::query()->count())->toBe(2) // traveller (upgraded) + admin
        ->and($traveller->fresh()->role)->toBe(UserRole::Business)
        ->and($traveller->fresh()->business?->name)->toBe('Nimal Surf School')
        ->and($traveller->fresh()->business?->approved)->toBeFalse()
        ->and($traveller->fresh()->business?->phone)->toBe('0719999999')
        ->and($traveller->fresh()->business?->phone_verified_at)->not->toBeNull();

    $this->assertAuthenticatedAs($traveller);

    Notification::assertSentTo($this->admin, ActivityNotification::class);
});

it('sends pending and approved partners to their own pages', function () {
    $pending = User::factory()->partner()->create();
    $business = Business::factory()->for($pending, 'user')->pending()->create();

    $this->actingAs($pending)->get('/partners/apply')->assertRedirect(route('partner.pending'));

    $business->forceFill(['approved' => true])->save();
    $pending->refresh();

    $this->actingAs($pending)->get('/partners/apply')->assertRedirect(route('partner.dashboard'));
});

it('keeps admins and travellers out of the pending room', function () {
    $this->actingAs($this->admin)->get('/partners/apply')->assertRedirect(route('admin.overview'));
    $this->actingAs($this->admin)->get('/partners/pending')->assertRedirect(route('admin.overview'));

    $traveller = User::factory()->create();

    $this->actingAs($traveller)->get('/partners/pending')->assertRedirect(route('partner.apply'));
});

it('will not create a second business for the same owner', function () {
    $partner = User::factory()->partner()->create();
    $business = Business::factory()->for($partner, 'user')->pending()->create();

    verifyPhone();

    $this->actingAs($partner)
        ->post('/partners/apply', [
            'name' => 'Someone Else',
            'phone' => '0771234567',
            'business_name' => 'Duplicate Business',
            'type' => 'villa',
            'city' => 'Galle',
            'facebook' => 'https://facebook.com/duplicate',
        ])
        ->assertRedirect(route('partner.pending'));

    expect(Business::query()->count())->toBe(1)
        ->and($business->fresh()->name)->not->toBe('Duplicate Business');
});
