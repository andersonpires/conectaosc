<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true]);
    ini_set('session.gc_maxlifetime', 86400);
    session_start();
    session_regenerate_id(true);
}

date_default_timezone_set('America/Sao_Paulo');

require_once $_SESSION['BASE_PATH'] . '/temp/openaikey.php';

class SwotAvaliacao
{
    public static function consolidarInsights(string $tema, array $insights, int $maxItens = 6): array
    {
        $insights = array_values(array_filter(array_map(function ($item) {
            $texto = trim(preg_replace('/\s+/', ' ', strip_tags((string) $item)));
            return $texto !== '' ? $texto : null;
        }, $insights)));

        if (empty($insights)) {
            return [];
        }

        $apiKey = $GLOBALS['apiKey'] ?? '';
        if (!is_string($apiKey) || strlen(trim($apiKey)) < 20) {
            return [];
        }

        $lista = '';
        foreach ($insights as $idx => $texto) {
            $n = $idx + 1;
            $lista .= "{$n}. {$texto}\n";
        }

        $prompt = <<<PROMPT
Você é especialista em análise SWOT e redação de insights.

TEMA: {$tema}

A partir da lista de insights abaixo:
- Agrupe ideias parecidas ou repetidas.
- Una em um insight mais claro e bem escrito.
- Descarte duplicidades.
- Retorne no máximo {$maxItens} insights finais.

Responda SOMENTE em JSON no formato:
["insight 1", "insight 2", "insight 3"]
PROMPT;

        $payload = [
            'model' => 'gpt-5.2',
            'instructions' => $prompt,
            'input' => $lista,
        ];

        $ch = curl_init('https://api.openai.com/v1/responses');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . trim($apiKey),
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 60,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);

        unset($ch);

        if ($curlErrno || $httpCode < 200 || $httpCode >= 300) {
            return [];
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return [];
        }

        $textoResposta = '';
        if (!empty($data['output']) && is_array($data['output'])) {
            foreach ($data['output'] as $item) {
                if (!empty($item['content']) && is_array($item['content'])) {
                    foreach ($item['content'] as $content) {
                        if (!empty($content['text'])) {
                            $textoResposta .= $content['text'];
                        }
                    }
                }
                if (!empty($item['text']) && is_string($item['text'])) {
                    $textoResposta .= $item['text'];
                }
            }
        }
        if ($textoResposta === '' && !empty($data['output_text']) && is_string($data['output_text'])) {
            $textoResposta = $data['output_text'];
        }

        $textoResposta = trim($textoResposta);
        if ($textoResposta === '') {
            return [];
        }

        $json = json_decode($textoResposta, true);
        if (is_array($json)) {
            return array_values(array_filter(array_map(function ($item) {
                $texto = trim(preg_replace('/\s+/', ' ', strip_tags((string) $item)));
                return $texto !== '' ? $texto : null;
            }, $json)));
        }

        $linhas = preg_split('/\r?\n/', $textoResposta);
        $resultado = [];
        foreach ($linhas as $linha) {
            $linha = preg_replace('/^\s*[-\d\.]+\s*/', '', trim($linha));
            if ($linha !== '') {
                $resultado[] = $linha;
            }
        }

        return $resultado;
    }
}

// Execucao direta via POST/GET retorna JSON
if (basename($_SERVER['SCRIPT_NAME']) === 'avaliarSwot.php') {
    $tema = $_POST['tema'] ?? $_GET['tema'] ?? '';
    $insights = $_POST['insights'] ?? $_GET['insights'] ?? [];

    if (is_string($insights)) {
        $insights = array_filter(array_map('trim', preg_split('/\r?\n/', $insights)));
    }
    if (!is_array($insights)) {
        $insights = [];
    }

    $resumo = SwotAvaliacao::consolidarInsights($tema, $insights);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'tema' => $tema,
        'insights' => $resumo,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
