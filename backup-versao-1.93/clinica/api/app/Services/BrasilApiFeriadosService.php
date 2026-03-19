<?php
namespace App\Services;

/**
 * Serviço para buscar feriados nacionais na Brasil API.
 * Mesmo padrão usado em conectaosc/app/feriados/BrasilApiService.
 */
class BrasilApiFeriadosService
{
    private const BASE_URL = 'https://brasilapi.com.br/api';

    public static function buscarFeriadosNacionais(int $ano): array
    {
        $url = self::BASE_URL . "/feriados/v1/{$ano}";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return [];
        }

        $feriados = json_decode($response, true);
        if (!is_array($feriados)) {
            return [];
        }

        $result = [];
        foreach ($feriados as $f) {
            $data = $f['date'] ?? null;
            if ($data) {
                $result[$data] = $f['name'] ?? 'Feriado';
            }
        }
        return $result;
    }
}
