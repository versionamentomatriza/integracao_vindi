<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\Plano;
use App\Models\PlanoEmpresa;
use Illuminate\Support\Facades\DB;

class Erp
{
    public static function makeCustomerParams($idEmpresa)
    {
        $empresa = Empresa::find($idEmpresa);

        return [
            'code' => $empresa->id,
            'name' => $empresa->nome,
            'email' => $empresa->email,
            'phones' => [
                [
                    "phone_type" => "mobile",
                    "number" => trim($empresa->celular)
                ]
            ]
        ];
    }

    public static function makeCheckoutParams($idEmpresa, $idPlano, $metodo_pgto)
    {
        $empresa = Empresa::find($idEmpresa);

        return [
            'customerCode' => $idEmpresa,
            'planCode' => $idPlano,
            'paymentMethod' => $metodo_pgto,
        ];
    }

    public static function getCustomerAddress($idEmpresa)
    {
        $empresa = Empresa::find($idEmpresa);

        return [
            'street' => $empresa->rua,
            'number' => $empresa->numero,
            'zip_code' => $empresa->cep,
            'city' => $empresa->cidade->nome,
            'state' => $empresa->cidade->uf,
            'country' => 'BR'
        ];
    }

    public static function getPlanDiscounts($planCode)
    {
        switch ((int) $planCode) {
            case 12:
                return [[
                    'amount' => 7000, // R$ 70,00 de desconto
                    'cycles' => 6,     // Aplica por 6 meses
                    'discount_type' => 'amount',
                ]];
                break;

            case 14:
                $planoEmpresa = PlanoEmpresa::where('empresa_id', $planCode)->first();
                $ciclos = $planoEmpresa->ciclos ?? null;
                if (!$ciclos || $ciclos <= 6) {
                    return [[
                        'amount' => 7000, // R$ 70,00 de desconto
                        'cycles' => 1,     // Aplica por 3 meses (trimestre) caso seja até dar 6 meses de desconto
                        'discount_type' => 'amount',
                    ]];
                }
                break;

            default:
                return [];
                break;
        }
    }

    public static function updatePlan($idEmpresa, $idPlano, $metodo_pgto)
    {
        $empresa = Empresa::findOrFail($idEmpresa);
        $plano = Plano::findOrFail($idPlano);
        $planoEmpresa = PlanoEmpresa::where('empresa_id', $empresa->id)->first();
        $ciclos = null;
        $intervaloDias = $plano->id === 1 ? 15 : 30;

        if ($idPlano == $plano->id) $ciclos = $planoEmpresa->ciclos ? $planoEmpresa->ciclos + 1 : 1;

        $planoEmpresa->update([
            'plano_id' => $plano->id,
            'ciclos' => $ciclos,
            'valor' => $plano->valor,
            'forma_pagamento' => $metodo_pgto,
            'data_expiracao' => DB::raw("DATE_ADD(data_expiracao, INTERVAL {$intervaloDias} DAY)"),
        ]);
    }
}
