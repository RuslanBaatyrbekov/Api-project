<?php

use App\Http\Controllers\FakeGatewayController;
use App\Http\Controllers\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('payments')->name('api.payments.')->group(function () {
    Route::post('/', [PaymentController::class, 'store'])->name('store');
    Route::get('/{id}', [PaymentController::class, 'show'])->name('show');
});

Route::post('/payment-callback', [PaymentController::class, 'webhook'])->name('api.payments.callback');

Route::post('/fake-gateway/pay', [FakeGatewayController::class, 'pay']);
