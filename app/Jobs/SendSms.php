<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sends one Text.lk message from the queue so booking/partner actions never
 * wait on the gateway.
 */
class SendSms implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $recipient,
        public string $message,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SmsService $sms): void
    {
        $sms->send($this->recipient, $this->message);
    }
}
