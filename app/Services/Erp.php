<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\Plano;
use App\Models\PlanoEmpresa;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class Erp
{
    public static function makeCustomerParams($idEmpresa)
    {
        $empresa = Empresa::find($idEmpresa);
        $documento = preg_replace('/\D/', '', $empresa->cpf_cnpj);
        $telefone = '55' . preg_replace('/\D/', '', $empresa->cobranca_telefone ?? $empresa->celular);

        return [
            'code' => $empresa->id,
            'name' => $empresa->cobranca_nome ?? $empresa->nome,
            'email' => $empresa->cobranca_email ?? $empresa->email,
            'registry_code' => $documento,
            'phones' => [
                [
                    'phone_type' => strlen($telefone) === 10 ? 'landline' : 'mobile',
                    "number" => $telefone,
                ]
            ]
        ];
    }

    public static function makeCheckoutParams($idEmpresa, $idPlano, $metodo_pgto)
    {
        $empresa = Empresa::find($idEmpresa);

        return [
            'customerCode' => $empresa->id,
            'planCode' => $idPlano,
            'paymentMethod' => $metodo_pgto,
        ];
    }

    public static function updatePlan($idEmpresa, $idPlano, $metodo_pgto)
    {
        try {
            $empresa = Empresa::findOrFail($idEmpresa);
            $plano   = Plano::findOrFail($idPlano);

            $planoEmpresa = PlanoEmpresa::where('empresa_id', $empresa->id)
                ->orderByDesc('id')
                ->first();

            // Mapeamento de dias por plano
            $dias = [
                1  => 15,
                2  => 30,
                3  => 30,
                4  => 30,
                11 => 30,
                14 => 30,
                16 => 30,
                18 => 30,
                22 => 30,
                5  => 90,
                6  => 90,
                7  => 90,
                15 => 90,
                17 => 90,
                19 => 90,
                23 => 90,
                12 => 91,
                13 => 183,
                21 => 91,
            ];

            $intervaloDias = $dias[$plano->id] ?? 365;

            // Se não existir PlanoEmpresa, cria um novo
            if (!$planoEmpresa) {
                $expiracao = now()->addDays($intervaloDias);

                PlanoEmpresa::create([
                    'empresa_id'        => $empresa->id,
                    'plano_id'          => $plano->id,
                    'data_expiracao'    => $expiracao,
                    'valor'             => $plano->valor ?? 0,
                    'forma_pagamento'   => $metodo_pgto
                ]);
                return;
            }

            // Se já existe, verifica se está vencido ou não
            if ($planoEmpresa->data_expiracao) {
                $expiracaoAtual = Carbon::parse($planoEmpresa->data_expiracao);

                if ($expiracaoAtual->isFuture()) {
                    // ⚠️ IMPORTANTE:
                    // Usamos ->copy() para não mutar $expiracaoAtual em memória,
                    // evitando adicionar dias "a mais" quando o mesmo objeto é reaproveitado.
                    $expiracao = $expiracaoAtual->copy()->addDays($intervaloDias);
                } else {
                    // Vencido → soma a partir de agora (data do pagamento)
                    $expiracao = now()->addDays($intervaloDias);
                }
            } else {
                // Nunca teve expiração
                $expiracao = now()->addDays($intervaloDias);
            }

            $planoEmpresa->update([
                'empresa_id'        => $empresa->id,
                'plano_id'          => $plano->id,
                'data_expiracao'    => $expiracao,
                'valor'             => $plano->valor ?? 0,
                'forma_pagamento'   => $metodo_pgto
            ]);
        } catch (\Throwable $th) {
            Log::channel('requests')->info('ERRO:' . $th->getMessage());
        }
    }

    public static function updateCustomerInfo($idEmpresa, $documento, $telefone)
    {
        $empresa = Empresa::find($idEmpresa);
        if ($empresa) {
            $empresa->cpf_cnpj = $documento;
            $empresa->celular = $telefone;
            $empresa->save();
        }
    }
}
