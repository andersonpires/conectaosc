<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_PATH = $runtime['base_path'];
$BASE_URL = $runtime['base_url'];
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true]);
    if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
}
    session_start();
    session_regenerate_id(true);
}


if (!isset($BASE_PATH) || !isset($BASE_URL)) {
    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    echo "event: error\n";
    echo "data: Sess?f?????T?f??s?,?o expirada. Fa?f?????T?f??s?,?a login novamente.\n\n";
    exit();
}

require_once $BASE_PATH . '/api/conectabd/conexao.php';
require_once $BASE_PATH . '/temp/openaikey.php';

set_time_limit(0);
ignore_user_abort(true);

header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-transform');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');
header('Content-Encoding: none');

while (ob_get_level() > 0) {
    ob_end_flush();
}
ob_implicit_flush(true);

function enviarEvento(string $evento, string $data): void
{
    $data = str_replace(["\r\n", "\r"], "\n", $data);
    echo "event: {$evento}\n";
    foreach (explode("\n", $data) as $linha) {
        echo 'data: ' . $linha . "\n";
    }
    echo "\n";
    if (function_exists('flush')) {
        flush();
    }
}

$idUsuario = $_POST['IdUsuario'] ?? null;
if (!$idUsuario || !is_numeric($idUsuario)) {
    enviarEvento('error', 'Usu?f?????T?f??s?,?rio inv?f?????T?f??s?,?lido.');
    exit;
}

$camposObrigatorios = [
    'Nome',
    'Nascimento',
    'NumPessoasReside',
    'ComQuemMora',
    'RendaMensal',
    'RendaFamiliar',
    'PCDEmCasa',
    'SituacaoMoradia',
    'TipoConstrucao',
    'AbastecimentoAgua',
    'EsgotamentoSanitario',
    'PossuiRedeEletrica',
    'PossuiIluminacaoPublica',
];

$labels = [
    'Nome' => 'Nome',
    'Nascimento' => 'Data de nascimento',
    'NumPessoasReside' => 'N?f?????T?f??s?,?mero de pessoas na resid?f?????T?f??s?,?ncia',
    'ComQuemMora' => 'Com quem mora',
    'RendaMensal' => 'Renda mensal',
    'RendaFamiliar' => 'Renda familiar',
    'PCDEmCasa' => 'PCD em casa',
    'SituacaoMoradia' => 'Situa?f?????T?f??s?,??f?????T?f??s?,?o de moradia',
    'TipoConstrucao' => 'Tipo da constru?f?????T?f??s?,??f?????T?f??s?,?o',
    'AbastecimentoAgua' => 'Abastecimento de ?f?????T?f??s?,?gua',
    'EsgotamentoSanitario' => 'Esgotamento sanit?f?????T?f??s?,?rio',
    'PossuiRedeEletrica' => 'Rede el?f?????T?f??s?,?trica',
    'PossuiIluminacaoPublica' => 'Ilumina?f?????T?f??s?,??f?????T?f??s?,?o p?f?????T?f??s?,?blica',
];

$camposFaltantes = [];
foreach ($camposObrigatorios as $campo) {
    if (!array_key_exists($campo, $_POST)) {
        $camposFaltantes[] = $labels[$campo] ?? $campo;
        continue;
    }
    $valor = $_POST[$campo];
    if ($valor === '' || $valor === null) {
        $camposFaltantes[] = $labels[$campo] ?? $campo;
    }
}

if (!empty($camposFaltantes)) {
    enviarEvento('error', 'Dados incompletos. Preencha: ' . implode(', ', $camposFaltantes));
    exit;
}

$stmt = $pdo->prepare("
    SELECT AvaliacaoVulnerabilidadeIA
    FROM tbAluno
    WHERE IdUsuario = ?
");
$stmt->execute([$idUsuario]);
$jaExiste = $stmt->fetchColumn();
if (!empty($jaExiste)) {
    enviarEvento('error', 'Avalia?f?????T?f??s?,??f?????T?f??s?,?o j?f?????T?f??s?,? existente. Utilize Reavaliar se necess?f?????T?f??s?,?rio.');
    exit;
}

$descricao = "Avalia?f?????T?f??s?,??f?????T?f??s?,?o socioassistencial baseada nos seguintes dados:\n\n";
foreach ($_POST as $campo => $valor) {
    if (is_array($valor)) {
        $valor = implode(', ', $valor);
    }
    $valor = trim((string)$valor);
    if ($valor !== '') {
        $descricao .= strtoupper($campo) . ": " . $valor . "\n";
    }
}

$prompt = <<<PROMPT
Voc?f?????T?f??s?,? ?f?????T?f??s?,? uma psic?f?????T?f??s?,?loga especializada em an?f?????T?f??s?,?lise psicossocial e de vulnerabilidades.

Com base nos dados fornecidos, realize:
1. Avalia?f?????T?f??s?,??f?????T?f??s?,?o se a pessoa est?f?????T?f??s?,? em situa?f?????T?f??s?,??f?????T?f??s?,?o de vulnerabilidade ou risco social
2. Classifica?f?????T?f??s?,??f?????T?f??s?,?o do n?f?????T?f??s?,?vel (Baixo, M?f?????T?f??s?,?dio ou Alto)
3. Justificativa clara, objetiva e humanizada, utilizando padr?f?????T?f??s?,?o acad?f?????T?f??s?,?mico e regras ABNT, alinhada ?f?????T?f??s?,?s pr?f?????T?f??s?,?ticas do Servi?f?????T?f??s?,?o Social

Responda no seguinte formato:

Classifica?f?????T?f??s?,??f?????T?f??s?,?o: (Possui / N?f?????T?f??s?,?o possui vulnerabilidade social)
N?f?????T?f??s?,?vel: (Baixo / M?f?????T?f??s?,?dio / Alto)
Justificativa:
Fundamenta?f?????T?f??s?,??f?????T?f??s?,?o:

A Justificativa e a Fundamenta?f?????T?f??s?,??f?????T?f??s?,?o devem conter apenas 1 par?f?????T?f??s?,?grafo cada.
Se a classifica?f?????T?f??s?,??f?????T?f??s?,?o ?f?????T?f??s?,? de que n?f?????T?f??s?,?o possui vulnerabilidade social, indique um par?f?????T?f??s?,?grafo extra com ideia de fala com acolhimento da negativa, como se dita por um psicologo, respondendo ao texto "Forma humana de explicar:".

Deixar os par?f?????T?f??s?,?grafos separados por uma linha e justificados, seguindo regras ABNT. Sempre pular uma linha a cada par?f?????T?f??s?,?grafo, lembrando que estamos exibindo o resultado do prompt em um text area.
PROMPT;

if (!$apiKey || strlen(trim($apiKey)) < 20) {
    enviarEvento('error', 'Chave da OpenAI n?f?????T?f??s?,?o configurada.');
    exit;
}

$payload = [
    'model' => 'gpt-5-nano',
    'instructions' => $prompt,
    'input' => $descricao,
    'stream' => true,
];


$resultado = '';
$buffer = '';

$ch = curl_init('https://api.openai.com/v1/responses');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . trim($apiKey),
    ],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_TIMEOUT => 300,
    CURLOPT_WRITEFUNCTION => function ($ch, $data) use (&$buffer, &$resultado) {
        $buffer .= $data;
        while (($pos = strpos($buffer, "\n\n")) !== false) {
            $chunk = substr($buffer, 0, $pos);
            $buffer = substr($buffer, $pos + 2);
            $lines = explode("\n", $chunk);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, 'data:') !== 0) {
                    continue;
                }
                $payload = trim(substr($line, 5));
                if ($payload === '' || $payload === '[DONE]') {
                    continue;
                }
                $json = json_decode($payload, true);
                if (!is_array($json)) {
                    continue;
                }
                $type = $json['type'] ?? '';
                if ($type === 'response.output_text.delta') {
                    $delta = $json['delta'] ?? '';
                    if ($delta !== '') {
                        $resultado .= $delta;
                        enviarEvento('delta', $delta);
                    }
                } elseif ($type === 'response.output_text.done') {
                    if (empty($resultado) && !empty($json['text'])) {
                        $resultado = (string)$json['text'];
                    }
                } elseif ($type === 'error') {
                    $msg = $json['error']['message'] ?? 'Erro desconhecido da OpenAI.';
                    enviarEvento('error', $msg);
                }
            }
        }
        return strlen($data);
    },
]);

$ok = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);
unset($ch);

if ($curlErrno) {
    enviarEvento('error', 'Erro de comunica?f?????T?f??s?,??f?????T?f??s?,?o com a OpenAI: ' . $curlError);
    exit;
}

if ($httpCode < 200 || $httpCode >= 300) {
    enviarEvento('error', 'Erro da OpenAI ao gerar a avalia?f?????T?f??s?,??f?????T?f??s?,?o.');
    exit;
}

$resultado = formatarAvaliacaoIA($resultado);
if ($resultado === '') {
    enviarEvento('error', 'A IA n?f?????T?f??s?,?o retornou uma avalia?f?????T?f??s?,??f?????T?f??s?,?o v?f?????T?f??s?,?lida.');
    exit;
}

$upd = $pdo->prepare("
    UPDATE tbAluno
    SET AvaliacaoVulnerabilidadeIA = ?
    WHERE IdUsuario = ?
");
$ok = $upd->execute([$resultado, $idUsuario]);
if (!$ok) {
    enviarEvento('error', 'Falha ao salvar a avalia?f?????T?f??s?,??f?????T?f??s?,?o no banco.');
    exit;
}

enviarEvento('done', $resultado);
exit;

function formatarAvaliacaoIA(string $texto): string
{
    $texto = str_replace(["\r\n", "\r"], "\n", $texto);

    $labels = [
        'Classifica?f?????T?f??s?,??f?????T?f??s?,?o:',
        'N?f?????T?f??s?,?vel:',
        'Justificativa:',
        'Fundamenta?f?????T?f??s?,??f?????T?f??s?,?o:',
        'Forma humana de explicar:'
    ];

    foreach ($labels as $label) {
        $texto = preg_replace(
            '/\s*' . preg_quote($label, '/') . '\s*/u',
            "\n\n" . $label . "\n",
            $texto
        );
    }

    $texto = ltrim($texto);
    $texto = preg_replace("/\n{3,}/", "\n\n", $texto);

    return trim($texto);
}




