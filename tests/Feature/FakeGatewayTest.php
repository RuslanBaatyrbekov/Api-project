<?php

namespace Tests\Feature;

use App\Jobs\SendFakeWebhook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FakeGatewayTest extends TestCase
{
    public function test_it_accepts_a_payment_and_queues_a_webhook(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/fake-gateway/pay', [
            'amount' => 10,
            'currency' => 'EUR',
            'callback_url' => 'https://shop.test/api/payment-callback',
        ]);

        $response->assertOk()->assertJsonPath('status', 'processing');
        $externalId = $response->json('external_id');
        $this->assertStringStartsWith('gw_', $externalId);

        Queue::assertPushed(SendFakeWebhook::class, fn (SendFakeWebhook $job) => $job->externalId === $externalId
            && $job->callbackUrl === 'https://shop.test/api/payment-callback'
            && in_array($job->status, ['success', 'failed'], true));
    }

    public function test_the_webhook_job_posts_the_final_status_to_the_callback_url(): void
    {
        Http::fake();

        (new SendFakeWebhook('https://shop.test/api/payment-callback', 'gw_1', 'success', delaySeconds: 0))->handle();

        Http::assertSent(fn ($request) => $request->url() === 'https://shop.test/api/payment-callback'
            && $request['external_id'] === 'gw_1'
            && $request['status'] === 'success');
    }

    public function test_it_requires_a_valid_callback_url(): void
    {
        $this->postJson('/api/fake-gateway/pay', ['amount' => 10, 'currency' => 'EUR', 'callback_url' => 'nope'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['callback_url']);
    }
}
