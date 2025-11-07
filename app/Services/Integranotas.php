<?php

namespace App\Services;

use App\Models\Empresa;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use CloudDfe\SdkPHP\Nfse;

class Integranotas
{
    /**
     * Emitir uma NFS-e via IntegraNotas
     */
    public static function createNFSe(array $dados)
    {
        try {
            $configSDK  = self::getConfig();
            $nfse       = new Nfse($configSDK);
            $tomador    = self::getTomador($dados['customer_code']);
            $servico    = self::getServico($dados['plan_code']);

            $payload = [
                "numero" => "",
                "serie" => "",
                "tipo" => "",
                "status" => "",
                "data_emissao" => "",
                "tomador" => $tomador,
                "servico" => $servico
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao emitir NFS-e: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private static function getConfig()
    {
        return [
            // Token do emitente obtido no painel da IntegraNotas no cadastro do emitente.
            // Para obter em Produção: https://gestao.integranotas.com.br/login e em Homologação: https://hom-gestao.integranotas.com.br/login
            "token" => config('services.integranotas.api_key'),

            // Em qual ambiente a requisição será feita.
            "ambiente" => 2, // IMPORTANTE: 1 - Produção / 2 - Homologação

            /*// Opções complementares, vai depender da sua necessidade
            "options" => [
                "debug" => "", // Ativa mensagem de depuração, Default: false
                "timeout" => "", // Tempo máximo de espera para resposta da API, Default: 60
                "port" => "", // Porta de conexão, Default: 443
                "http_version" => "" // Versão do HTTP, Default: CURL_HTTP_VERSION_NONE
            ]*/
        ];
    }

    private static function getTomador($empresaId)
    {
        $empresa = Empresa::find($empresaId);

        return [
            "cnpj" => self::isCNPJ($empresa->cpf_cnpj) ? $empresa->cpf_cnpj : "",
            "cpf" => self::isCNPJ($empresa->cpf_cnpj) ? "" : $empresa->cpf_cnpj,
            "im" => $empresa->inscricao_municipal,
            "razao_social" => $empresa->nome,
            "endereco" => [
                "logradouro" => $empresa->rua,
                "numero" => $empresa->numero,
                "complemento" => $empresa->complemento,
                "bairro" => $empresa->bairro,
                "codigo_municipio" => $empresa->cidade->codigo,
                "uf" => $empresa->cidade->uf,
                "cep" => $empresa->cep
            ]
        ];
    }

    private static function getServico($planoId)
    {
        $matriza = Empresa::find(14);
        return [
            "codigo_municipio" => $matriza->cidade->codigo,
            "itens" => [
                [
                    "codigo" => "",
                    "codigo_tributacao_municipio" => "",
                    "discriminacao" => "",
                    "valor_servicos" => "",
                    "valor_pis" => "",
                    "valor_cofins" => "",
                    "valor_inss" => "",
                    "valor_ir" => "",
                    "valor_csll" => "",
                    "valor_outras" => "",
                    "valor_aliquota" => "",
                    "valor_desconto_incondicionado" => ""
                ]
            ]
        ];
    }

    private static function isCNPJ(string $valor): bool
    {
        $numero = preg_replace('/\D/', '', $valor);
        return strlen($numero) === 14;
    }
}
