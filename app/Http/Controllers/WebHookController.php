<?php

namespace App\Http\Controllers;

use App\Models\FaturaVindiProcessada;
use App\Services\Erp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebHookController extends Controller
{
    public function handle(Request $request, $token)
    {
        Log::channel('requests')->info(json_encode($request->all()));

        $validToken = config('services.integration.token');
        if ($token !== $validToken) return response()->json(['error' => 'Unauthorized'], 401);

        $event = $request->input('event') ?? null;

        if ($event && ($event['type'] ?? null) === 'bill_paid') {
            $bill = $event['data']['bill'] ?? null;

            if ($bill && FaturaVindiProcessada::where('fatura_id', $bill['id'])->exists()) return response('Duplicate', 200);

            FaturaVindiProcessada::create(['fatura_id' => $bill['id']]);

            $plan     = $bill['subscription']['plan'] ?? null;
            $customer = $bill['customer'] ?? null;
            $paymentMethod = $bill['charges'][0]['payment_method']['name']
                ?? $bill['charges'][0]['last_transaction']['gateway_response_fields']['payment_method_name']
                ?? 'Desconhecido';

            if ($plan && $customer) {
                Erp::updatePlan(
                    (int) $customer['code'],
                    (int) $plan['code'],
                    $paymentMethod
                );

                // Emitir NFS-e pelo ERP
                Http::post(route('https://matriza.net/public/api/nfse/emitir_nota_vindi'), [
                    'empresa_id' => $customer['code'],
                    'plano_id' => $plan['code'],
                ]);
            }
        }
    }
}
