<?php

namespace App\Http\Controllers;

use App\Services\Erp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebHookController extends Controller
{
    public function handle(Request $request, $token)
    {
        $validToken = config('services.integration.token');
        if ($token !== $validToken) return response()->json(['error' => 'Unauthorized'], 401);

        $event = $request->input('event') ?? null;

        if ($event && $event['type'] === 'bill_paid') {
            Log::channel('stderr')->info('Requisição recebida: ' . json_encode($request->all()));
            $plan = $event['data']['bill']['subscription']['plan'];
            $customer = $event['data']['bill']['customer'];
            $amount =$bill['amount'] ?? 0;
            Erp::updatePlan($customer['code'], $plan['code'], $amount);
        }
    }
}
