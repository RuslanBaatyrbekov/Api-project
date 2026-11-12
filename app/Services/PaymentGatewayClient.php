<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentGatewayClient
{
    /**
     * Sends a payment creation request to the external gateway.
     *
     * @return string External ID
     *
     * @throws Exception
     */
    public function initiatePayment(array $data): string
    {
        $gatewayUrl = config('services.payment_gateway.url');

        try {
            $response = Http::post($gatewayUrl, [
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'description' => $data['description'],
                'callback_url' => route('api.payments.callback'),
            ]);

            if ($response->failed()) {
                Log::error('Gateway error', ['body' => $response->body(), 'status' => $response->status()]);
                throw new Exception('Gateway returned error: '.$response->status());
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
