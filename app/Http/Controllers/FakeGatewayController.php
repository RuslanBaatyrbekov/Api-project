<?php

namespace App\Http\Controllers;

use App\Jobs\SendFakeWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FakeGatewayController extends Controller
{
    /**
     * Эмуляция приема платежа банком.
     */
    public function pay(Request $request)
    {
        $request->validate([
            'amount' => 'required',
            'currency' => 'required',
            'callback_url' => 'required|url'
        ]);

        $externalId = 'gw_' . Str::random(10);

        $finalStatus = rand(1, 10) > 5 ? 'success' : 'failed';

        SendFakeWebhook::dispatch(
            $request->input('callback_url'),
            $externalId,
            $finalStatus
        )->delay(now()->addSeconds(1));

        return response()->json([
            'external_id' => $externalId,
            'status' => 'processing',
        ]);
    }
}
