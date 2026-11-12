<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CreatePaymentTest extends TestCase
{
    use RefreshDatabase;

    private const GATEWAY_URL = 'https://gateway.test/pay';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.payment_gateway.url' => self::GATEWAY_URL]);
    }

    public function test_it_creates_a_payment_and_registers_it_with_the_gateway(): void
    {
        Http::fake([self::GATEWAY_URL => Http::response(['external_id' => 'gw_abc123', 'status' => 'processing'])]);

        $response = $this->postJson('/api/payments', [
            'amount' => 1000,
            'currency' => 'EUR',
            'description' => 'Order #123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', PaymentStatus::PROCESSING->value)
            ->assertJsonPath('external_id', 'gw_abc123');

        $this->assertDatabaseHas('payments', [
            'currency' => 'EUR',
            'external_id' => 'gw_abc123',
            'status' => PaymentStatus::PROCESSING->value,
        ]);

        Http::assertSent(fn (Request $request) => $request->url() === self::GATEWAY_URL
            && $request['amount'] == 1000
            && $request['currency'] === 'EUR'
            && $request['callback_url'] === route('api.payments.callback'));
    }

    public function test_it_rolls_back_the_payment_when_the_gateway_fails(): void
    {
        Http::fake([self::GATEWAY_URL => Http::response('Internal error', 500)]);

        $this->postJson('/api/payments', [
            'amount' => 50,
            'currency' => 'USD',
            'description' => 'Order #124',
        ])->assertStatus(502)->assertJsonPath('error', 'Payment Gateway Unavailable');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_it_rejects_a_gateway_response_without_external_id(): void
    {
        Http::fake([self::GATEWAY_URL => Http::response(['status' => 'processing'])]);

        $this->postJson('/api/payments', [
            'amount' => 50,
            'currency' => 'USD',
            'description' => 'Order #125',
        ])->assertStatus(502);

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_it_validates_the_payload(): void
    {
        Http::fake();

        $this->postJson('/api/payments', [
            'amount' => 0,
            'currency' => 'RUB',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['amount', 'currency', 'description']);

        Http::assertNothingSent();
    }

    public function test_it_shows_a_payment(): void
    {
        $payment = Payment::create([
            'amount' => 10,
            'currency' => 'KGS',
            'description' => 'Coffee',
            'status' => PaymentStatus::PAID,
            'external_id' => 'gw_paid',
        ]);

        $this->getJson("/api/payments/{$payment->id}")
            ->assertOk()
            ->assertJsonPath('status', 'paid')
            ->assertJsonPath('amount', '10.00');
    }

    public function test_it_returns_404_for_an_unknown_payment(): void
    {
        $this->getJson('/api/payments/999')->assertNotFound();
    }
}
