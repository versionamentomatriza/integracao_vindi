<?php

namespace App\Services;

use App\Mail\SendNFSeMail;
use App\Models\Empresa;
use App\Models\Plano;
use Illuminate\Support\Facades\Log;
use CloudDfe\SdkPHP\Nfse;
use Illuminate\Support\Facades\Mail;

class Integranotas
{
    /**
     * Emitir uma NFS-e via IntegraNotas
     */
    public static function createNFSe(array $dados)
    {
        try {
            $emitente           = Empresa::find(14);
            $configSDK          = self::getConfig();
            $nfse               = new Nfse($configSDK);
            $empresa            = Empresa::find($dados['customer_code']);
            $tomador            = self::getTomador($empresa);
            $numero             = $emitente->numero_ultima_nfse + 1;
            $itemServico        = self::getItemServico($dados['plan_code']);

            $payload = [
                "modelo" => "municipal",
                "numero" => $numero,
                "serie" => $emitente->numero_serie_nfse,
                "tipo" => "1",
                "status" => "1",
                "data_emissao" => date("Y-m-d\TH:i:sP"),
                "data_competencia" => date("Y-m-d\TH:i:sP"),
                "tomador" => $tomador,
                "servico" => [
                    "codigo_municipio" => $emitente->cidade->codigo,
                    "itens" => [$itemServico]
                ]
            ];

            // Envia a NFSe para a API
            $resp = $nfse->cria($payload);

            if ($resp->sucesso) {
                // Ao entrar nesse bloco significa que a NFSe foi para o provedor e aguarda processamento.

                // Salva a chave no banco de dados para receber depois o resultado se a nota foi autorizada ou rejeitada
                // OBS: A chave é o identificador para consultas futuras da NFSe
                $chave = $resp->chave;

                sleep(15); // Aguarda 15 segundos para consultar a NFse, pois o processamento pode levar alguns segundos
                $payload = ["chave" => $chave];
                $resp = $nfse->consulta($payload);

                if ($resp->codigo != 5023) {
                    if ($resp->sucesso) {
                        // Mandar email para o cliente com a NFSe
                        if (!empty($resp->pdf)) {
                            $pdfContent = base64_decode($resp->pdf);
                            $nfseNumber = $resp->numero ?? $numero; // fallback se a API não retornar o número
                            $pdfPath = storage_path("app/nfse/{$nfseNumber}.pdf");
                            file_put_contents($pdfPath, $pdfContent);
                        }

                        // --- Monta os dados pro e-mail ---
                        $mailData = [
                            'number'    => $nfseNumber,
                            'name'      => $empresa->nome,
                            'link'      => $resp->link_nfse ?? null, // algumas prefeituras retornam esse link
                        ];

                        // --- Envia o e-mail pro cliente ---
                        if (!empty($empresa->email)) {
                            Mail::to($empresa->email)->queue(new SendNFSeMail($mailData));
                            Log::channel('nfse')->info("[NFSE] E-mail enviado para {$empresa->email} - NFSe {$nfseNumber}");
                        } else Log::channel('nfse')->info("[NFSE] E-mail não enviado pois a empresa não possui e-mail cadastrado - NFSe {$nfseNumber}");
                    } else Log::channel('nfse')->info('[NFSE] ' . json_encode($resp));
                }

                return [
                    'success' => true,
                    'error' => null,
                ];
            } else if (in_array($resp->codigo, [5001, 5002])) {
                // Aqui o retorno indica que houve um erro na validação dos dados enviados
                // O código 5001 indica que falto campos obrigatórios ou opcionais obrigatórios referente ao emitente.
                // O código 5002 indica que houve um erro na validação dos dados como CNPJ, CPF, Inscrição Estadual, etc.
                Log::channel('nfse')->info('[NFSE] ' . json_encode($resp->erros));
            } else Log::channel('nfse')->info('[NFSE] Certificado digital não informado ou erro inesperado.'); // Aqui é retornado qualquer erro que não seja relacionado a validação dos dados como não foi informado certificado digital, entre outros.
        } catch (\Exception $e) {
            Log::channel('nfse')->info('[NFSE] Erro ao emitir NFS-e: ' . $e->getMessage());
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
            "ambiente" => Nfse::AMBIENTE_PRODUCAO, // IMPORTANTE: 1 - Produção / 2 - Homologação

            /*// Opções complementares, vai depender da sua necessidade
            "options" => [
                "debug" => "", // Ativa mensagem de depuração, Default: false
                "timeout" => "", // Tempo máximo de espera para resposta da API, Default: 60
                "port" => "", // Porta de conexão, Default: 443
                "http_version" => "" // Versão do HTTP, Default: CURL_HTTP_VERSION_NONE
            ]*/
        ];
    }

    private static function getTomador($empresa)
    {
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

    private static function getItemServico($planoId)
    {
        $plano = Plano::find($planoId);

        return [
            "codigo"                      => "107",
            "codigo_tributacao_municipio" => "1.07",
            "discriminacao"               => "Plano empresarial",
            "valor_servicos"              => $plano->valor,
            "valor_aliquota"              => "2.00"
        ];
    }

    private static function isCNPJ(string $valor): bool
    {
        $numero = preg_replace('/\D/', '', $valor);
        return strlen($numero) === 14;
    }
}
