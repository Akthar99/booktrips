<?php

namespace App\Services;

use App\Jobs\SendSms;
use Illuminate\Http\Client\PendingRequest;
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
     * Queue a message so the triggering request never waits on the gateway.
     *
     * Use send() only when the user is actively waiting for the message
     * (one-time codes); everything else belongs here.
     */
    public function queue(?string $recipient, ?string $message): void
    {
        if ($recipient === null || $recipient === '' || $message === null || $message === '') {
            return;
        }

        try {
            dispatch(new SendSms($recipient, $message));
        } catch (Throwable $exception) {
            report($exception);
        }
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

        $sender = (string) config('booktrips.sms.sender_id');

        if (mb_strlen($sender) > 11) {
            Log::warning("Text.lk sender_id \"{$sender}\" is longer than 11 characters and will be rejected.", [
                'hint' => 'Set TEXTLK_SENDER_ID to a registered 11-character sender id.',
            ]);
        }

        try {
            $response = $this->client()
                ->post(rtrim((string) config('booktrips.sms.base_url'), '/').'/sms/send', [
                    'recipient' => $recipient,
                    'sender_id' => $sender,
                    'type' => 'plain',
                    'message' => $message,
                ]);
        } catch (Throwable $exception) {
            // Almost always a TLS/DNS problem on the host: log the precise reason
            // (a missing CA bundle shows up here) without leaking it to the user.
            Log::error('SMS could not reach Text.lk: '.$exception->getMessage(), [
                'recipient' => $recipient,
                'hint' => 'On Windows hosts, set curl.cainfo in php.ini or BOOKTRIPS_CA_BUNDLE to a cacert.pem path.',
            ]);

            return false;
        }

        $payload = $response->json();

        if (! $response->successful() || (($payload['status'] ?? 'success') !== 'success')) {
            Log::warning('Text.lk rejected the SMS', [
                'status' => $response->status(),
                'response' => $payload ?? $response->body(),
                'recipient' => $recipient,
                'sender_id' => $sender,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Authenticated JSON client, optionally pinned to an explicit CA bundle.
     */
    private function client(): PendingRequest
    {
        $request = Http::withToken((string) config('booktrips.sms.api_key'))
            ->acceptJson()
            ->asJson()
            ->timeout(12);

        $bundle = config('booktrips.http.ca_bundle');

        if (is_string($bundle) && $bundle !== '') {
            $request = $request->withOptions(['verify' => $bundle]);
        }

        return $request;
    }
}
