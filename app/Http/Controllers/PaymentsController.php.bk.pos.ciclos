<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscriptionRequest;
use App\Services\ApiResponse;
use App\Services\Erp;
use App\Services\VindiApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class PaymentsController extends Controller
{
    // subscriptions
    public function credit_card(SubscriptionRequest $request)
    {
        $data = Erp::makeCheckoutParams($request->input('id_empresa'), $request->input('id_plano'), 'credit_card');
        $token = Crypt::encrypt($data);
        $url = url('/payments/checkout?t=' . urlencode($token));
        return ApiResponse::success('Rota gerada com sucesso.', ['url' => $url]);
    }

    public function bank_slip(SubscriptionRequest $request)
    {
        $data = Erp::makeCheckoutParams($request->input('id_empresa'), $request->input('id_plano'), 'pix_bank_slip');
        $token = Crypt::encrypt($data);
        $url = url('/payments/checkout?t=' . urlencode($token));
        return ApiResponse::success('Rota gerada com sucesso.', ['url' => $url]);
    }

    public function pix(SubscriptionRequest $request)
    {
        $data = Erp::makeCheckoutParams($request->input('id_empresa'), $request->input('id_plano'), 'pix');
        $token = Crypt::encrypt($data);
        $url = url('/payments/checkout?t=' . urlencode($token));
        return ApiResponse::success('Rota gerada com sucesso.', ['url' => $url]);
    }

    public function checkout(Request $request)
    {
        try {
            $token = $request->query('t');
            $data = Crypt::decrypt($token);
            $planCode = $data['planCode'];
            $customerCode = $data['customerCode'];
            $paymentMethod = $data['paymentMethod'];

            // pegando id do plano correspondente na Vindi
            $plan = VindiApi::getPlanByCode($planCode);
            $planId = $plan['id'] ?? null;

            // pegando o id do cliente correspondente na Vindi
            $customer = VindiApi::getCustomerByCode($customerCode);
            $customerId = $customer['id'] ?? null;

            if (!$customerId) {
                // criando um cliente na Vindi caso não exista
                $customerParams = Erp::makeCustomerParams($customerCode);
                $customer = VindiApi::createCustomer($customerParams);

                if (!$customer) return ApiResponse::error('Erro ao criar cliente na Vindi.');
                else $customerId = $customer['id'];
            }

            $address = ERP::getCustomerAddress($customerCode);
            $discounts = ERP::getPlanDiscounts($planCode);

            $response = VindiApi::subscribe($planId, $customerId, $address, $paymentMethod, $discounts);
            $paymentUrl = $response['bill']['url'];

            if (!empty($paymentUrl)) return redirect()->away($paymentUrl);

            // algo deu errado
            abort(500, 'Erro ao gerar checkout na Vindi');
        } catch (\Exception $e) {
            abort(403, 'Token inválido ou expirado');
        }
    }
}
