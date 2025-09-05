<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscriptionRequest;
use App\Services\ApiResponse;
use App\Services\Erp;
use App\Services\VindiApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

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

            // pegando dados na Vindi
            $plan = VindiApi::getPlanByCode($planCode);
            $customer = VindiApi::getCustomerByCode($customerCode);

            $planId = (is_array($plan) && isset($plan['id'])) ? $plan['id'] : null;
            $customerId = (is_array($customer) && isset($customer['id'])) ? $customer['id'] : null;

            if (!$customerId) {
                // criando um cliente na Vindi caso não exista
                $customerParams = Erp::makeCustomerParams($customerCode);
                $customer = VindiApi::createCustomer($customerParams);

                if (!$customer) {
                    $token = Crypt::encrypt($data);
                    return redirect()->route('payments.update_customer_info', ['t' => $token]);
                } else $customerId = $customer['id'];
            }

            $response = VindiApi::subscribe($planId, $customerId, $paymentMethod);
            $paymentUrl = $response['bill']['url'];

            if (!empty($paymentUrl)) return redirect()->away($paymentUrl);

            // algo deu errado
            abort(500, 'Erro ao gerar checkout na Vindi');
        } catch (\Exception $e) {
            abort(403, 'Opss! algo inesperado aconteceu.');
        }
    }

    public function update_customer_info(Request $request)
    {
        $token = $request->query('t');
        $data = Crypt::decrypt($token);

        return view('payments.update_customer_info', compact('data'));
    }

    public function save_customer_info(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cpf_cnpj' => [
                'required',
                'regex:/^(\d{3}\.\d{3}\.\d{3}-\d{2}|\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2})$/'
            ],
            'telefone' => [
                'required',
                'regex:/^\(\d{2}\) \d{4,5}-\d{4}$/'
            ],
        ], [
            'cpf_cnpj.required' => 'O CPF ou CNPJ é obrigatório.',
            'cpf_cnpj.regex' => 'O CPF ou CNPJ deve estar no formato correto.',
            'telefone.required' => 'O telefone é obrigatório.',
            'telefone.regex' => 'O telefone deve estar no formato correto, como (11) 91234-5678.',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput(); // mantém os dados preenchidos no formulário
        }

        $empresaId  = (int) $request->input('empresa_id');
        $planoId    = (int) $request->input('empresa_id');
        $metodoPgto = $request->input('metodo_pgto');
        $documento  = $request->input('cpf_cnpj');
        $telefone   = $request->input('telefone');

        Erp::updateCustomerInfo($empresaId, $documento, $telefone);

        $data   = Erp::makeCheckoutParams($empresaId, $planoId, $metodoPgto);
        $token  = Crypt::encrypt($data);
        $url    = url('/payments/checkout?t=' . urlencode($token));

        return redirect($url);
    }
}
