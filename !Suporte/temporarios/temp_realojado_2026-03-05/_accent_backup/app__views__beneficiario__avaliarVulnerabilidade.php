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



require_once $BASE_PATH . '/api/legacy/checa-token.php';
require_once $BASE_PATH . '/api/conectabd/conexao.php';
require_once $BASE_PATH . '/temp/openaikey.php';
$beneficiariosCadastroUrl = rtrim((string) $BASE_URL, '/') . '/beneficiarios/cadastro';

/*
-------------------------------------------------------
 Valida?f?????T?f??s?,??f?????T?f??s?,?o b?f?????T?f??s?,?sica do usu?f?????T?f??s?,?rio
-------------------------------------------------------
*/
$idUsuario = $_POST['IdUsuario'] ?? null;

if (!$idUsuario || !is_numeric($idUsuario)) {
    header("Location: {$beneficiariosCadastroUrl}?erro=Usu?f?????T?f??s?,?rio inv?f?????T?f??s?,?lido");
    exit;
}

/*
-------------------------------------------------------
 Campos obrigat?f?????T?f??s?,?rios
-------------------------------------------------------
*/
$camposObrigatorios = [
    // Dados pessoais
    'Nome',
    'Nascimento',

    // Dados econ?f?????T?f??s?,?micos e socioassistenciais
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

// Labels amig?f?????T?f??s?,?veis
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

/*
-------------------------------------------------------
 Valida?f?????T?f??s?,??f?????T?f??s?,?o dos dados recebidos
-------------------------------------------------------
*/
$camposFaltantes = [];

foreach ($camposObrigatorios as $campo) {

    if (!array_key_exists($campo, $_POST)) {
        $camposFaltantes[] = $labels[$campo] ?? $campo;
        continue;
    }

    $valor = $_POST[$campo];

    // aceita "0", mas n?f?????T?f??s?,?o aceita vazio
    if ($valor === '' || $valor === null) {
        $camposFaltantes[] = $labels[$campo] ?? $campo;
    }
}

if (!empty($camposFaltantes)) {
    $lista = implode(', ', $camposFaltantes);
    header(
        "Location: {$beneficiariosCadastroUrl}?id={$idUsuario}&erro=" .
            urlencode("Dados incompletos. Preencha: {$lista}")
    );
    exit;
}

/*
-------------------------------------------------------
 Montagem do texto estruturado para IA
-------------------------------------------------------
*/
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

/*
-------------------------------------------------------
 Prompt (system)
-------------------------------------------------------
*/
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

/*
-------------------------------------------------------
 Chamada ?f?????T?f??s?,? OpenAI (Responses API)
-------------------------------------------------------
*/
if (!$apiKey || strlen(trim($apiKey)) < 20) {
    header("Location: {$beneficiariosCadastroUrl}?id={$idUsuario}&erro=Chave da OpenAI n?f?????T?f??s?,?o configurada");
    exit;
}

$payload = [
    'model' => 'gpt-5-nano',
    'instructions' => $prompt,
    'input' => $descricao,
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

if ($curlErrno) {
    header(
        "Location: {$beneficiariosCadastroUrl}?id={$idUsuario}&erro=" .
            urlencode("Erro de comunica?f?????T?f??s?,??f?????T?f??s?,?o com a OpenAI: {$curlError}")
    );
    exit;
}

$data = json_decode($response, true);

if (!is_array($data)) {
    header(
        "Location: {$beneficiariosCadastroUrl}?id={$idUsuario}&erro=" .
            urlencode("Resposta inv?f?????T?f??s?,?lida da OpenAI.")
    );
    exit;
}


if ($httpCode < 200 || $httpCode >= 300) {
    $msg = $data['error']['message'] ?? 'Erro desconhecido da OpenAI';
    header(
        "Location: {$beneficiariosCadastroUrl}?id={$idUsuario}&erro=" .
            urlencode("Erro OpenAI: {$msg}")
    );
    exit;
}

/*
-------------------------------------------------------
 Extra?f?????T?f??s?,??f?????T?f??s?,?o robusta do texto retornado
-------------------------------------------------------
*/
$resultado = '';

if (!empty($data['output']) && is_array($data['output'])) {
    foreach ($data['output'] as $item) {

        // padr?f?????T?f??s?,?o novo
        if (!empty($item['content']) && is_array($item['content'])) {
            foreach ($item['content'] as $content) {
                if (!empty($content['text'])) {
                    $resultado .= $content['text'];
                }
            }
        }

        // fallback simples
        if (!empty($item['text']) && is_string($item['text'])) {
            $resultado .= $item['text'];
        }
    }
}


$resultado = formatarAvaliacaoIA($resultado);

if ($resultado === '') {
    header(
        "Location: {$beneficiariosCadastroUrl}?id={$idUsuario}&erro=" .
            urlencode("A IA n?f?????T?f??s?,?o retornou uma avalia?f?????T?f??s?,??f?????T?f??s?,?o v?f?????T?f??s?,?lida.")
    );
    exit;
}
if ($resultado === '' && !empty($data['output_text']) && is_string($data['output_text'])) {
    $resultado = trim($data['output_text']);
}


/*
-------------------------------------------------------
 Salva no banco
-------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT AvaliacaoVulnerabilidadeIA
    FROM tbAluno
    WHERE IdUsuario = ?
");
$stmt->execute([$idUsuario]);
$jaExiste = $stmt->fetchColumn();

if (!empty($jaExiste)) {
    header(
        "Location: {$beneficiariosCadastroUrl}?id={$idUsuario}&erro=" .
            urlencode("Avalia?f?????T?f??s?,??f?????T?f??s?,?o j?f?????T?f??s?,? existente. Utilize Reavaliar se necess?f?????T?f??s?,?rio.")
    );
    exit;
}

// AGORA SIM: salva a avalia?f?????T?f??s?,??f?????T?f??s?,?o
$upd = $pdo->prepare("
    UPDATE tbAluno
    SET AvaliacaoVulnerabilidadeIA = ?
    WHERE IdUsuario = ?
");
$ok = $upd->execute([$resultado, $idUsuario]);

if (!$ok) {
    header(
        "Location: {$beneficiariosCadastroUrl}?id={$idUsuario}&erro=" .
            urlencode("Falha ao salvar a avalia?f?????T?f??s?,??f?????T?f??s?,?o no banco.")
    );
    exit;
}


/*
-------------------------------------------------------
 Redireciona de volta ao formul?f?????T?f??s?,?rio
-------------------------------------------------------
*/
header(
    "Location: {$beneficiariosCadastroUrl}?id={$idUsuario}&msg=" .
        urlencode("Avalia?f?????T?f??s?,??f?????T?f??s?,?o de vulnerabilidade realizada com sucesso")
);
exit;

/*
-------------------------------------------------------
 Formata?f?????T?f??s?,??f?????T?f??s?,?o final da avalia?f?????T?f??s?,??f?????T?f??s?,?o (quebras de bloco)
-------------------------------------------------------
*/
function formatarAvaliacaoIA(string $texto): string
{
    // Normaliza quebras
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

    // Remove excesso de linhas no in?f?????T?f??s?,?cio
    $texto = ltrim($texto);

    // Limpa linhas em branco excessivas (m?f?????T?f??s?,?x 2)
    $texto = preg_replace("/\n{3,}/", "\n\n", $texto);

    return trim($texto);
}




