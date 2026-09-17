<?php

namespace App\Services;

use App\Models\PhoneVerification;
use Illuminate\Support\Facades\Hash;

/**
 * Issues and checks the one-time codes used to prove a partner's mobile number.
 *
 * Codes are stored hashed, expire quickly, allow a handful of wrong guesses and
 * can only be requested again after a cooldown, which keeps an SMS endpoint from
 * being used as a free messaging relay.
 */
class PhoneVerificationService
{
    public const PURPOSE_PARTNER = 'partner_application';

    public const PURPOSE_BOOKING = 'booking';

    public function __construct(private readonly SmsService $sms) {}

    /**
     * Normalise a Sri Lankan mobile number to the 947XXXXXXXX form Text.lk wants.
     */
    public function normalise(?string $phone): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0094')) {
            $digits = substr($digits, 4);
        } elseif (str_starts_with($digits, '94') && strlen($digits) === 11) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = substr($digits, 1);
        }

        if (! preg_match('/^7\d{8}$/', $digits)) {
            return null;
        }

        return '94'.$digits;
    }

    /**
     * Send a fresh code, honouring the cooldown and hourly cap.
     *
     * @return array{ok: bool, error: string|null, cooldown: int, phone: string|null}
     */
    public function issue(?string $phone, ?string $ip, string $purpose = self::PURPOSE_PARTNER): array
    {
        $normalised = $this->normalise($phone);

        if ($normalised === null) {
            return $this->fail('Enter a valid Sri Lankan mobile number, for example 077 123 4567.');
        }

        $cooldown = (int) config('booktrips.sms.otp.resend_cooldown_seconds', 60);

        $last = PhoneVerification::query()
            ->where('phone', $normalised)
            ->where('purpose', $purpose)
            ->latest('id')
            ->first();

        $age = $last?->created_at !== null ? now()->getTimestamp() - $last->created_at->getTimestamp() : null;

        if ($age !== null && $age < $cooldown) {
            $wait = $cooldown - $age;

            return $this->fail("Please wait {$wait} seconds before requesting another code.", $wait);
        }

        $perHour = (int) config('booktrips.sms.otp.max_per_hour', 5);
        $recent = PhoneVerification::query()
            ->where('phone', $normalised)
            ->where('purpose', $purpose)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recent >= $perHour) {
            return $this->fail('Too many codes requested for this number. Try again in an hour.');
        }

        $length = (int) config('booktrips.sms.otp.length', 6);
        $code = (string) random_int((int) str_pad('1', $length, '0'), (int) str_pad('', $length, '9'));
        $minutes = (int) config('booktrips.sms.otp.ttl_minutes', 10);

        $verification = PhoneVerification::create([
            'phone' => $normalised,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes($minutes),
            'ip' => $ip,
        ]);

        $message = str_replace(
            [':code', ':minutes'],
            [$code, (string) $minutes],
            (string) config('booktrips.sms.otp.message'),
        );

        if (! $this->sms->send($normalised, $message)) {
            $verification->delete();

            return $this->fail('We could not send the SMS just now. Please try again in a moment.');
        }

        return ['ok' => true, 'error' => null, 'cooldown' => $cooldown, 'phone' => $normalised];
    }

    /**
     * Check a code and, when it matches, remember the verified number on the session.
     *
     * @return array{ok: bool, error: string|null, phone: string|null}
     */
    public function confirm(?string $phone, ?string $code, string $purpose = self::PURPOSE_PARTNER): array
    {
        $normalised = $this->normalise($phone);

        if ($normalised === null) {
            return $this->fail('Enter a valid Sri Lankan mobile number, for example 077 123 4567.');
        }

        $code = preg_replace('/[^0-9]/', '', (string) $code) ?? '';

        if ($code === '') {
            return $this->fail('Enter the code from the SMS.');
        }

        $verification = PhoneVerification::query()
            ->where('phone', $normalised)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $verification || $verification->isExpired()) {
            return $this->fail('That code has expired. Request a new one.');
        }

        $maxAttempts = (int) config('booktrips.sms.otp.max_attempts', 5);

        if ($verification->attempts >= $maxAttempts) {
            $verification->forceFill(['consumed_at' => now()])->save();

            return $this->fail('Too many wrong codes. Request a new one.');
        }

        $verification->increment('attempts');

        if (! Hash::check($code, $verification->code_hash)) {
            return $this->fail('That code does not match. Check the SMS and try again.');
        }

        $verification->forceFill(['consumed_at' => now()])->save();

        $this->rememberSession($normalised, $purpose);

        return ['ok' => true, 'error' => null, 'phone' => $normalised];
    }

    /**
     * The number verified in this session for a purpose, if the proof is still fresh.
     */
    public function sessionVerifiedPhone(string $purpose = self::PURPOSE_PARTNER): ?string
    {
        $session = session($this->sessionKey($purpose));

        if (! is_array($session) || ! isset($session['phone'], $session['at'])) {
            return null;
        }

        $minutes = (int) config('booktrips.sms.otp.session_minutes', 30);

        if (now()->getTimestamp() - (int) $session['at'] > $minutes * 60) {
            $this->forgetSession($purpose);

            return null;
        }

        return (string) $session['phone'];
    }

    public function forgetSession(string $purpose = self::PURPOSE_PARTNER): void
    {
        session()->forget($this->sessionKey($purpose));
    }

    private function rememberSession(string $phone, string $purpose): void
    {
        session([$this->sessionKey($purpose) => ['phone' => $phone, 'at' => now()->getTimestamp()]]);
    }

    private function sessionKey(string $purpose): string
    {
        return 'booktrips.phone.'.$purpose;
    }

    /**
     * @return array{ok: false, error: string, cooldown: int, phone: null}
     */
    private function fail(string $error, int $cooldown = 0): array
    {
        return ['ok' => false, 'error' => $error, 'cooldown' => $cooldown, 'phone' => null];
    }
}
