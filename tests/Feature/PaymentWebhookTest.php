<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function processingPayment(): Payment
    {
        return Payment::create([
            'amount' => 99.90,
            'currency' => 'EUR',
            'description' => 'Order #200',
            'status' => PaymentStatus::PROCESSING,
            'external_id' => 'gw_200',
        ]);
    }

    public static function gatewayStatuses(): array
    {
        return [
            'success' => ['success', PaymentStatus::PAID],
            'failed' => ['failed', PaymentStatus::FAILED],
        ];
    }

    #[DataProvider('gatewayStatuses')]
    public function test_it_applies_the_final_gateway_status(string $gatewayStatus, PaymentStatus $expected): void
    {
        $payment = $this->processingPayment();

        $this->postJson('/api/payment-callback', [
            'external_id' => 'gw_200',
            'status' => $gatewayStatus,
        ])->assertOk()->assertJson(['status' => 'ok']);

        $this->assertSame($expected, $payment->fresh()->status);
    }

    public function test_it_is_idempotent_for_already_finalised_payments(): void
    {
        $payment = $this->processingPayment();

        $this->postJson('/api/payment-callback', ['external_id' => 'gw_200', 'status' => 'success'])->assertOk();
        $this->postJson('/api/payment-callback', ['external_id' => 'gw_200', 'status' => 'failed'])->assertOk();

        $this->assertSame(PaymentStatus::PAID, $payment->fresh()->status);
    }

    public function test_it_returns_404_for_an_unknown_external_id(): void
    {
        $this->postJson('/api/payment-callback', [
            'external_id' => 'gw_missing',
            'status' => 'success',
        ])->assertNotFound();
    }

    public function test_it_validates_the_webhook_payload(): void
    {
        $this->postJson('/api/payment-callback', ['status' => 'refunded'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['external_id', 'status']);
    }
}
