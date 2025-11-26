<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendFakeWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $callbackUrl,
        public string $externalId,
        public string $status
    ) {}

    public function handle(): void
    {
        sleep(2);

        Log::info("FakeGateway: Sending webhook for {$this->externalId} to {$this->callbackUrl}");

        Http::post($this->callbackUrl, [
            'external_id' => $this->externalId,
            'status' => $this->status,
        ]);
    }
}
