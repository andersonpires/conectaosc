<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true]);
    ini_set('session.gc_maxlifetime', 86400);
    session_start();
    session_regenerate_id(true);
}

date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    echo "event: error\n";
    echo "data: Sessão expirada. Faça login novamente.\n\n";
    exit();
}

require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';
require_once $_SESSION['BASE_PATH'] . '/temp/openaikey.php';

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
    enviarEvento('error', 'Usuário inválido.');
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
    'NumPessoasReside' => 'Número de pessoas na residência',
    'ComQuemMora' => 'Com quem mora',
    'RendaMensal' => 'Renda mensal',
    'RendaFamiliar' => 'Renda familiar',
    'PCDEmCasa' => 'PCD em casa',
    'SituacaoMoradia' => 'Situação de moradia',
    'TipoConstrucao' => 'Tipo da construção',
    'AbastecimentoAgua' => 'Abastecimento de água',
    'EsgotamentoSanitario' => 'Esgotamento sanitário',
    'PossuiRedeEletrica' => 'Rede elétrica',
    'PossuiIluminacaoPublica' => 'Iluminação pública',
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
    enviarEvento('error', 'Avaliação já existente. Utilize Reavaliar se necessário.');
    exit;
}

$descricao = "Avaliação socioassistencial baseada nos seguintes dados:\n\n";
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
Você é uma psicóloga especializada em análise psicossocial e de vulnerabilidades.

Com base nos dados fornecidos, realize:
1. Avaliação se a pessoa está em situação de vulnerabilidade ou risco social
2. Classificação do nível (Baixo, Médio ou Alto)
3. Justificativa clara, objetiva e humanizada, utilizando padrão acadêmico e regras ABNT, alinhada às práticas do Serviço Social

Responda no seguinte formato:

Classificação: (Possui / Não possui vulnerabilidade social)
Nível: (Baixo / Médio / Alto)
Justificativa:
Fundamentação:

A Justificativa e a Fundamentação devem conter apenas 1 parágrafo cada.
Se a classificação é de que não possui vulnerabilidade social, indique um parágrafo extra com ideia de fala com acolhimento da negativa, como se dita por um psicologo, respondendo ao texto "Forma humana de explicar:".

Deixar os parágrafos separados por uma linha e justificados, seguindo regras ABNT. Sempre pular uma linha a cada parágrafo, lembrando que estamos exibindo o resultado do prompt em um text area.
PROMPT;

if (!$apiKey || strlen(trim($apiKey)) < 20) {
    enviarEvento('error', 'Chave da OpenAI não configurada.');
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
    enviarEvento('error', 'Erro de comunicação com a OpenAI: ' . $curlError);
    exit;
}

if ($httpCode < 200 || $httpCode >= 300) {
    enviarEvento('error', 'Erro da OpenAI ao gerar a avaliação.');
    exit;
}

$resultado = formatarAvaliacaoIA($resultado);
if ($resultado === '') {
    enviarEvento('error', 'A IA não retornou uma avaliação válida.');
    exit;
}

$upd = $pdo->prepare("
    UPDATE tbAluno
    SET AvaliacaoVulnerabilidadeIA = ?
    WHERE IdUsuario = ?
");
$ok = $upd->execute([$resultado, $idUsuario]);
if (!$ok) {
    enviarEvento('error', 'Falha ao salvar a avaliação no banco.');
    exit;
}

enviarEvento('done', $resultado);
exit;

function formatarAvaliacaoIA(string $texto): string
{
    $texto = str_replace(["\r\n", "\r"], "\n", $texto);

    $labels = [
        'Classificação:',
        'Nível:',
        'Justificativa:',
        'Fundamentação:',
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
