<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PaymentGatewayClient
{
    /**
     * Отправляет запрос на создание платежа во внешний шлюз.
     *
     * @param array $data
     * @return string External ID
     * @throws Exception
     */
    public function initiatePayment(array $data): string
    {
        $gatewayUrl = 'http://127.0.0.1:8001/api/fake-gateway/pay';

        try {
            $response = Http::post($gatewayUrl, [
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'description' => $data['description'],
                'callback_url' => route('api.payments.callback'),
            ]);

            if ($response->failed()) {
                Log::error('Gateway error', ['body' => $response->body(), 'status' => $response->status()]);
                throw new Exception('Gateway returned error: ' . $response->status());
            }

            $json = $response->json();

            if (empty($json['external_id'])) {
                throw new Exception('Gateway did not return external_id');
            }

            return $json['external_id'];

        } catch (\Exception $e) {
            Log::critical('Payment Gateway Connection Failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
