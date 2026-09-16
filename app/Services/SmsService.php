<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Thin client for the Text.lk SMS gateway.
 *
 * When no API key is configured the message is written to the log instead,
 * mirroring how MAIL_MAILER=log behaves locally, so development and tests can
 * still complete the flow.
 */
class SmsService
{
    public function configured(): bool
    {
        return is_string(config('booktrips.sms.api_key')) && config('booktrips.sms.api_key') !== '';
    }

    /**
     * Send one plain text message. Returns false when the gateway rejected it.
     */
    public function send(string $recipient, string $message): bool
    {
        if (! $this->configured()) {
            Log::info("SMS (log driver) to {$recipient}: {$message}");

            return true;
        }

        try {
            $response = Http::withToken((string) config('booktrips.sms.api_key'))
                ->acceptJson()
                ->asJson()
                ->timeout(12)
                ->post(rtrim((string) config('booktrips.sms.base_url'), '/').'/sms/send', [
                    'recipient' => $recipient,
                    'sender_id' => (string) config('booktrips.sms.sender_id'),
                    'type' => 'plain',
                    'message' => $message,
                ]);
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }

        if (! $response->successful()) {
            Log::warning('SMS gateway error', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            return false;
        }

        return ($response->json('status') ?? 'success') === 'success';
    }
}
