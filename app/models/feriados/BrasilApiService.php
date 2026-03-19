<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
class BrasilApiService
{
    private const BASE_para_URL = 'https://brasilapi.com.br/api';

    public static function buscarFeriadosNacionais(int $ano): array
    {
        $url = self::BASE_para_URL . "/feriados/v1/{$ano}";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            throw new Exception("Erro ao buscar feriados nacionais: HTTP {$httpCode}");
        }

        $feriados = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Erro ao decodificar resposta da API: " . json_last_error_msg());
        }

        if (!is_array($feriados)) {
            return [];
        }

        $feriadosFormatados = [];
        foreach ($feriados as $feriado) {
            $feriadosFormatados[] = [
                'data' => $feriado['date'] ?? null,
                'nome' => $feriado['name'] ?? 'Feriado',
                'tipo' => 'nacional'
            ];
        }

        return $feriadosFormatados;
    }
}

