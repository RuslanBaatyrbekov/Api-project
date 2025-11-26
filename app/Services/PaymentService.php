<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        protected PaymentGatewayClient $gatewayClient
    ) {}

    /**
     * Создание платежа и отправка в шлюз
     */
    public function createPayment(array $validatedData): Payment
    {
        return DB::transaction(function () use ($validatedData) {
            $payment = Payment::create([
                'amount' => $validatedData['amount'],
                'currency' => $validatedData['currency'],
                'description' => $validatedData['description'],
                'status' => PaymentStatus::CREATED,
            ]);
            $externalId = $this->gatewayClient->initiatePayment($validatedData);

            $payment->update([
                'status' => PaymentStatus::PROCESSING,
                'external_id' => $externalId,
            ]);

            return $payment;
        });
    }

    /**
     * Обработка вебхука
     */
    public function handleWebhook(string $externalId, string $gatewayStatus): void
    {
        $payment = Payment::where('external_id', $externalId)->first();

        if (!$payment) {
            Log::warning("Webhook: Payment not found for external_id: $externalId");
            abort(404, 'Payment not found');
        }

        if (in_array($payment->status, [PaymentStatus::PAID, PaymentStatus::FAILED])) {
            Log::info("Webhook: Payment {$payment->id} already processed. Skipping.");
            return;
        }

        $newStatus = match ($gatewayStatus) {
            'success' => PaymentStatus::PAID,
            'failed' => PaymentStatus::FAILED,
            default => null,
        };

        if ($newStatus) {
            $payment->update(['status' => $newStatus]);
            Log::info("Webhook: Payment {$payment->id} status updated to {$newStatus->value}");
        }
    }
}
