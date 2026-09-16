<?php

use App\Models\PhoneVerification;
use App\Services\PhoneVerificationService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
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
});

it('normalises Sri Lankan mobile numbers', function () {
    $service = app(PhoneVerificationService::class);

    expect($service->normalise('0771234567'))->toBe('94771234567')
        ->and($service->normalise('+94 77 123 4567'))->toBe('94771234567')
        ->and($service->normalise('94771234567'))->toBe('94771234567')
        ->and($service->normalise('0094771234567'))->toBe('94771234567')
        ->and($service->normalise('0112345678'))->toBeNull()
        ->and($service->normalise('nonsense'))->toBeNull()
        ->and($service->normalise(null))->toBeNull();
});

it('sends one SMS through Text.lk with the expected payload', function () {
    $this->post('/partners/apply/phone', ['phone' => '0771234567'])
        ->assertOk()
        ->assertJson(['ok' => true, 'cooldown' => 60]);

    Http::assertSentCount(1);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://app.text.lk/api/v3/sms/send'
            && $request->hasHeader('Authorization', 'Bearer test-key')
            && $request['recipient'] === '94771234567'
            && $request['sender_id'] === 'BookTrips'
            && $request['type'] === 'plain'
            && str_contains((string) $request['message'], 'BookTrips.lk');
    });

    $verification = PhoneVerification::query()->firstOrFail();

    expect($verification->phone)->toBe('94771234567')
        ->and($verification->attempts)->toBe(0)
        ->and($verification->consumed_at)->toBeNull()
        ->and($verification->expires_at->isFuture())->toBeTrue();
});

it('rejects an invalid number without calling the gateway', function () {
    $this->post('/partners/apply/phone', ['phone' => '0112345678'])
        ->assertStatus(422)
        ->assertJson(['ok' => false]);

    Http::assertNothingSent();
    expect(PhoneVerification::query()->count())->toBe(0);
});

it('holds a cooldown between codes for the same number', function () {
    $this->post('/partners/apply/phone', ['phone' => '0771234567'])->assertOk();

    $second = $this->post('/partners/apply/phone', ['phone' => '0771234567']);

    $second->assertStatus(422);
    expect($second->json('cooldown'))->toBeGreaterThan(0);

    Http::assertSentCount(1);
});

it('caps how many codes one number can request per hour', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->post('/partners/apply/phone', ['phone' => '0771234567'])->assertOk();
        $this->travel(61)->seconds();
    }

    $this->post('/partners/apply/phone', ['phone' => '0771234567'])
        ->assertStatus(422)
        ->assertJson(['ok' => false]);
});

it('throttles code requests per IP across numbers', function () {
    config(['booktrips.sms.otp.resend_cooldown_seconds' => 0]);

    for ($i = 0; $i < 6; $i++) {
        $this->post('/partners/apply/phone', ['phone' => '077123456'.$i])->assertOk();
    }

    $this->post('/partners/apply/phone', ['phone' => '0771234569'])->assertStatus(429);
});

it('verifies the right code and remembers the number on the session', function () {
    $this->post('/partners/apply/phone', ['phone' => '0771234567']);

    $this->post('/partners/apply/phone/confirm', ['phone' => '0771234567', 'code' => $this->code])
        ->assertOk()
        ->assertJson(['ok' => true]);

    expect(app(PhoneVerificationService::class)->sessionVerifiedPhone())->toBe('94771234567');
});

it('counts wrong codes and burns the code after too many tries', function () {
    config(['booktrips.sms.otp.max_attempts' => 3]);

    $this->post('/partners/apply/phone', ['phone' => '0771234567']);

    $wrong = $this->code === '111111' ? '222222' : '111111';

    foreach (range(1, 3) as $attempt) {
        $this->post('/partners/apply/phone/confirm', ['phone' => '0771234567', 'code' => $wrong])
            ->assertStatus(422);
    }

    // The right code is refused once the attempts budget is spent.
    $this->post('/partners/apply/phone/confirm', ['phone' => '0771234567', 'code' => $this->code])
        ->assertStatus(422);

    expect(app(PhoneVerificationService::class)->sessionVerifiedPhone())->toBeNull();
});

it('expires codes that were never used', function () {
    $this->post('/partners/apply/phone', ['phone' => '0771234567']);

    $this->travel(11)->minutes();

    $this->post('/partners/apply/phone/confirm', ['phone' => '0771234567', 'code' => $this->code])
        ->assertStatus(422)
        ->assertJson(['message' => 'That code has expired. Request a new one.']);
});
