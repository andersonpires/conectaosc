<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true]);
    ini_set('session.gc_maxlifetime', 86400);
    session_start();
    session_regenerate_id(true);
}

date_default_timezone_set('America/Sao_Paulo');


require_once $_SESSION['BASE_PATH'] . '/checa-token.php';
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';
require_once $_SESSION['BASE_PATH'] . '/temp/openaikey.php';

/*
-------------------------------------------------------
 Validação básica do usuário
-------------------------------------------------------
*/
$idUsuario = $_POST['IdUsuario'] ?? null;

if (!$idUsuario || !is_numeric($idUsuario)) {
    header("Location: beneficiarioController.php?erro=Usuário inválido");
    exit;
}

/*
-------------------------------------------------------
 Campos obrigatórios
-------------------------------------------------------
*/
$camposObrigatorios = [
    // Dados pessoais
    'Nome',
    'Nascimento',

    // Dados econômicos e socioassistenciais
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

// Labels amigáveis
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

/*
-------------------------------------------------------
 Validação dos dados recebidos
-------------------------------------------------------
*/
$camposFaltantes = [];

foreach ($camposObrigatorios as $campo) {

    if (!array_key_exists($campo, $_POST)) {
        $camposFaltantes[] = $labels[$campo] ?? $campo;
        continue;
    }

    $valor = $_POST[$campo];

    // aceita "0", mas não aceita vazio
    if ($valor === '' || $valor === null) {
        $camposFaltantes[] = $labels[$campo] ?? $campo;
    }
}

if (!empty($camposFaltantes)) {
    $lista = implode(', ', $camposFaltantes);
    header(
        "Location: beneficiarioController.php?id={$idUsuario}&erro=" .
            urlencode("Dados incompletos. Preencha: {$lista}")
    );
    exit;
}

/*
-------------------------------------------------------
 Montagem do texto estruturado para IA
-------------------------------------------------------
*/
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

/*
-------------------------------------------------------
 Prompt (system)
-------------------------------------------------------
*/
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

/*
-------------------------------------------------------
 Chamada à OpenAI (Responses API)
-------------------------------------------------------
*/
if (!$apiKey || strlen(trim($apiKey)) < 20) {
    header("Location: beneficiarioController.php?id={$idUsuario}&erro=Chave da OpenAI não configurada");
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
        "Location: beneficiarioController.php?id={$idUsuario}&erro=" .
            urlencode("Erro de comunicação com a OpenAI: {$curlError}")
    );
    exit;
}

$data = json_decode($response, true);

if (!is_array($data)) {
    header(
        "Location: beneficiarioController.php?id={$idUsuario}&erro=" .
            urlencode("Resposta inválida da OpenAI.")
    );
    exit;
}


if ($httpCode < 200 || $httpCode >= 300) {
    $msg = $data['error']['message'] ?? 'Erro desconhecido da OpenAI';
    header(
        "Location: beneficiarioController.php?id={$idUsuario}&erro=" .
            urlencode("Erro OpenAI: {$msg}")
    );
    exit;
}

/*
-------------------------------------------------------
 Extração robusta do texto retornado
-------------------------------------------------------
*/
$resultado = '';

if (!empty($data['output']) && is_array($data['output'])) {
    foreach ($data['output'] as $item) {

        // padrão novo
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
        "Location: beneficiarioController.php?id={$idUsuario}&erro=" .
            urlencode("A IA não retornou uma avaliação válida.")
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
        "Location: beneficiarioController.php?id={$idUsuario}&erro=" .
            urlencode("Avaliação já existente. Utilize Reavaliar se necessário.")
    );
    exit;
}

// AGORA SIM: salva a avaliação
$upd = $pdo->prepare("
    UPDATE tbAluno
    SET AvaliacaoVulnerabilidadeIA = ?
    WHERE IdUsuario = ?
");
$ok = $upd->execute([$resultado, $idUsuario]);

if (!$ok) {
    header(
        "Location: beneficiarioController.php?id={$idUsuario}&erro=" .
            urlencode("Falha ao salvar a avaliação no banco.")
    );
    exit;
}


/*
-------------------------------------------------------
 Redireciona de volta ao formulário
-------------------------------------------------------
*/
header(
    "Location: beneficiarioController.php?id={$idUsuario}&msg=" .
        urlencode("Avaliação de vulnerabilidade realizada com sucesso")
);
exit;

/*
-------------------------------------------------------
 Formatação final da avaliação (quebras de bloco)
-------------------------------------------------------
*/
function formatarAvaliacaoIA(string $texto): string
{
    // Normaliza quebras
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

    // Remove excesso de linhas no início
    $texto = ltrim($texto);

    // Limpa linhas em branco excessivas (máx 2)
    $texto = preg_replace("/\n{3,}/", "\n\n", $texto);

    return trim($texto);
}
