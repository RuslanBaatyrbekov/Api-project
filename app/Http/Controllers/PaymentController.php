<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\WebhookRequest;
use App\Models\Payment;
use App\Services\PaymentService;
use Exception;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * 1. Создание платежа
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        try {
            $payment = $this->paymentService->createPayment($request->validated());

            return response()->json($payment, 201);
        } catch (Exception $e) {
            return response()->json(['error' => 'Payment Gateway Unavailable', 'message' => $e->getMessage()], 502);
        }
    }

    /**
     * 2. Получение информации
     */
    public function show($id): JsonResponse
    {
        $payment = Payment::findOrFail($id);
        return response()->json($payment);
    }

    /**
     * 3. Вебхук
     */
    public function webhook(WebhookRequest $request): JsonResponse
    {
        $this->paymentService->handleWebhook(
            $request->input('external_id'),
            $request->input('status')
        );

        return response()->json(['status' => 'ok']);
    }
}
