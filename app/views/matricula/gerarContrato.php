<?php
if (ob_get_level() === 0) {
    ob_start();
}
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
}

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: " . rtrim((string) ($BASE_para_URL ?? ''), '/') . "/login/?redirect=$redirect_url");
    exit();
}

require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';
require_once $BASE_para_PATH . '/api/lib/tcpdf/tcpdf.php';
require_once $BASE_para_PATH . '/api/lib/tcpdf/fpdi/autoload.php';
require_once __DIR__ . '/contratoAssinaturaDigital.php';

$idUsuario = isset($_GET['aluno']) ? intval($_GET['aluno']) : 0;
$idTurma = isset($_GET['turma']) ? intval($_GET['turma']) : 0;

if ($idUsuario <= 0 || $idTurma <= 0) {
    die("<div class='alert alert-danger'>Parâmetros inválidos.</div>");
}

$sql = $pdo->prepare(" 
    SELECT a.*, c.NomeCurso, t.NomeTurma, m.IdMatricula, m.vData, p.LogoProjeto, p.TermosContrato
      FROM tbMatricula m
      JOIN tbAluno a ON m.IdUsuario = a.IdUsuario
      JOIN tbCurso c ON m.IdCurso = c.IdCurso
      JOIN tbTurma t ON m.IdTurma = t.IdTurma
      LEFT JOIN tbProjeto p ON c.IdProjeto = p.IdProjeto
     WHERE m.IdUsuario = ?
       AND m.IdTurma = ?
     LIMIT 1
");
$sql->execute([$idUsuario, $idTurma]);
$dados = $sql->fetch(PDO::FETCH_ASSOC);

if (!$dados) {
    die("<div class='alert alert-danger'>Aluno não encontrado nesta turma.</div>");
}

$nome = $dados['Nome'] ?? '';
$cpf = $dados['CPF'] ?? '';
$nascimento = $dados['Nascimento'] ?? '';
$endereco = $dados['Endereco'] ?? '';
$bairro = $dados['Bairro'] ?? '';
$cidade = $dados['Cidade'] ?? '';
$uf = $dados['UF'] ?? '';
$telefone = $dados['Telefone'] ?? '';
$whatsapp = $dados['WhatsApp'] ?? '';
$responsavel = $dados['NomeResp1'] ?? '';
$cpfResp = $dados['CpfResp1'] ?? '';
$telefoneResp = $dados['TelefoneResp1'] ?? $dados['WhatsAppResp1'] ?? '';
$logoProjeto = $dados['LogoProjeto'] ?? '';
$termosContrato = $dados['TermosContrato'] ?? '';
$nomeDocumentoContrato = 'Contrato - ' . $nome;

if (isset($_GET['ajax']) && $_GET['ajax'] === 'historico') {
    header('Content-Type: application/json; charset=UTF-8');

    try {
        $stmtHistorico = $pdo->prepare("
            SELECT NomeArquivo, AssinaturaBase64, TimestampAssinatura, urlValidacao
              FROM tbpdf_assinado
             WHERE NomeDocumento = ?
          ORDER BY TimestampAssinatura DESC
             LIMIT 20
        ");
        $stmtHistorico->execute([$nomeDocumentoContrato]);
        $historico = $stmtHistorico->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $items = array_map(static function (array $row) use ($BASE_para_URL): array {
            $nomeArquivo = trim((string)($row['NomeArquivo'] ?? ''));
            $downloadUrl = $nomeArquivo !== ''
                ? rtrim((string)$BASE_para_URL, '/') . '/app/storage/assinatura/assinados/' . rawurlencode($nomeArquivo)
                : '';

            return [
                'nomeArquivo' => $nomeArquivo,
                'codigo' => (string)($row['AssinaturaBase64'] ?? ''),
                'timestampAssinatura' => (int)($row['TimestampAssinatura'] ?? 0),
                'dataAssinatura' => !empty($row['TimestampAssinatura']) ? date('d/m/Y H:i:s', (int)$row['TimestampAssinatura']) : '',
                'downloadUrl' => $downloadUrl,
                'validacaoUrl' => (string)($row['urlValidacao'] ?? ''),
            ];
        }, $historico);

        echo json_encode([
            'ok' => true,
            'nomeAluno' => $nome,
            'items' => $items,
            'novoContratoUrl' => rtrim((string)$BASE_para_URL, '/') . '/matriculas/contrato/?turma=' . $idTurma . '&aluno=' . $idUsuario,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'erro' => 'Erro ao consultar contratos emitidos.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    exit;
}

function resolveProjectLogoPath($logoRaw, $basePath)
{
    return bootstrap_resolve_assets_img_file(is_string($logoRaw) ? $logoRaw : '');
}

class ContratoPDF extends TCPDF
{
    public $logoPath = '';
    public $logoSistemaPath = '';
    public $logoHeightMm = 20;
    public $headerTopMm = 8;
    public $headerLineGapMm = 2;

    public function Header()
    {
        $logoTop = $this->headerTopMm;
        if ($this->logoSistemaPath && is_file($this->logoSistemaPath)) {
            $this->Image($this->logoSistemaPath, 15, $logoTop, 0, $this->logoHeightMm, '', '', 'T', false, 300);
        }
        if ($this->logoPath && is_file($this->logoPath)) {
            $rightX = $this->getPageWidth() - 15 - 35;
            $this->Image($this->logoPath, $rightX, $logoTop, 0, $this->logoHeightMm, '', '', 'T', false, 300);
        }
        $lineY = $logoTop + $this->logoHeightMm + $this->headerLineGapMm;
        $this->Line(15, $lineY, $this->getPageWidth() - 15, $lineY);
    }

    public function Footer()
    {
        $pageHeight = $this->getPageHeight();
        $lineY = $pageHeight - 18;
        $this->Line(15, $lineY, $this->getPageWidth() - 15, $lineY);
        $this->SetY($lineY + 2);
        $this->SetFont('helvetica', '', 8);
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

$config = $pdo->query(" 
    SELECT c.LogoImpressao,
           c.CargoDirigente,
           c.IdColaboradorDirigente,
           u.Nome AS NomeDirigente,
           u.Sobrenome AS SobrenomeDirigente
      FROM tbConfig c
 LEFT JOIN tbUser u ON u.IdColaborador = c.IdColaboradorDirigente
     LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

$logoSistema = $config['LogoImpressao'] ?? '';
$logoSistemaPath = resolveProjectLogoPath($logoSistema, $BASE_para_PATH);
$responsavelSistema = 'Instituto Tecnológico e Vocacional Avançado - ITEVA';
$nomeDirigente = trim((string)(($config['NomeDirigente'] ?? '') . ' ' . ($config['SobrenomeDirigente'] ?? '')));
$cargoDirigente = trim((string)($config['CargoDirigente'] ?? ''));
$linhaDirigenteCargo = trim($nomeDirigente . ($cargoDirigente !== '' ? (' - ' . $cargoDirigente) : ''));
$idDirigente = (int)($config['IdColaboradorDirigente'] ?? 0);

$pdf = new ContratoPDF();
$pdf->logoPath = resolveProjectLogoPath($logoProjeto, $BASE_para_PATH);
$pdf->logoSistemaPath = $logoSistemaPath;
$pdf->SetCreator('Conecta OSC');
$pdf->SetAuthor('Conecta OSC');
$pdf->SetTitle('Contrato - ' . $nome);
$pdf->SetMargins(15, 40, 15);
$pdf->SetHeaderMargin(0);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 11);

$responsavelRows = '';
if (trim($responsavel) !== '') {
    $responsavelRows .= '<tr><td><b>Nome</b></td><td>' . htmlspecialchars($responsavel) . '</td></tr>';
}
if (trim($cpfResp) !== '') {
    $responsavelRows .= '<tr><td><b>CPF</b></td><td>' . htmlspecialchars($cpfResp) . '</td></tr>';
}
if (trim($telefoneResp) !== '') {
    $responsavelRows .= '<tr><td><b>Contato</b></td><td>' . htmlspecialchars($telefoneResp) . '</td></tr>';
}

$responsavelHtml = '';
if ($responsavelRows !== '') {
    $responsavelHtml = '<h4>Responsável</h4><table cellpadding="4" border="1">' . $responsavelRows . '</table>';
}

$termosHtml = '';
$termosContrato = trim((string)$termosContrato);
if ($termosContrato !== '') {
    $termosHtml = html_entity_decode($termosContrato);
}

$temDadosResponsavel = trim($responsavel) !== '' || trim($cpfResp) !== '' || trim($telefoneResp) !== '';
$nomeAssinaturaBeneficiarioOuResponsavel = $temDadosResponsavel
    ? (trim($responsavel) !== '' ? $responsavel : 'Responsável')
    : $nome;

$assinaturaHtml = '';
if (trim((string)$responsavelSistema) !== '') {
    $assinaturaHtml .= '<div style="clear:both; page-break-inside:avoid;"><table cellpadding="0" cellspacing="0" border="0"><tr><td style="height:42mm;"></td></tr></table><p style="text-align:center;">______________________________________________<br>';
    if ($linhaDirigenteCargo !== '') {
        $assinaturaHtml .= '<b>' . htmlspecialchars($linhaDirigenteCargo) . '</b><br>';
    }
    $assinaturaHtml .= htmlspecialchars($responsavelSistema);
    $assinaturaHtml .= '</p>';
}
$assinaturaHtml .= '<table cellpadding="0" cellspacing="0" border="0"><tr><td style="height:12mm;"></td></tr></table><p style="text-align:center;">______________________________________________<br>' . htmlspecialchars($nomeAssinaturaBeneficiarioOuResponsavel) . '</p></div>';

$htmlConteudo = '
<h2 style="text-align:center;"><b>TERMO DE RESPONSABILIDADE E COMPROMISSO</b></h2>
<h4>Dados do Beneficiário</h4>
<table cellpadding="4" border="1">
    <tr><td><b>Nome</b></td><td>' . htmlspecialchars($nome) . '</td></tr>
    <tr><td><b>Data de nascimento</b></td><td>' . htmlspecialchars($nascimento) . '</td></tr>
    <tr><td><b>CPF</b></td><td>' . htmlspecialchars($cpf) . '</td></tr>
    <tr><td><b>Endereço</b></td><td>' . htmlspecialchars($endereco) . '</td></tr>
    <tr><td><b>Bairro</b></td><td>' . htmlspecialchars($bairro) . '</td></tr>
    <tr><td><b>Cidade/UF</b></td><td>' . htmlspecialchars(trim($cidade . ' / ' . $uf)) . '</td></tr>
    <tr><td><b>Telefone</b></td><td>' . htmlspecialchars($telefone) . '</td></tr>
    <tr><td><b>WhatsApp</b></td><td>' . htmlspecialchars($whatsapp) . '</td></tr>
</table>
' . $responsavelHtml . $termosHtml;

while (ob_get_level() > 0) {
    ob_end_clean();
}

$pathBaseAssinatura = rtrim($BASE_para_PATH, '/\\') . '/app/storage/assinatura/';
$pathOriginais = $pathBaseAssinatura . 'originais/';
if (!is_dir($pathOriginais)) {
    @mkdir($pathOriginais, 0775, true);
}

$idAssinante = $idDirigente > 0 ? $idDirigente : (int)($_SESSION['Cod'] ?? 0);
$nomeAssinante = $nomeDirigente !== '' ? $nomeDirigente : trim((string)(($_SESSION['Nome'] ?? '') . ' ' . ($_SESSION['Sobrenome'] ?? '')));
if ($nomeAssinante === '') {
    $nomeAssinante = 'Dirigente';
}

$timestampArquivo = time();
$nomeArquivoFinal = 'contrato_' . $timestampArquivo . '_' . $idAssinante . '.pdf';
$pathOriginalTemp = $pathOriginais . 'contrato_orig_' . $timestampArquivo . '_' . $idAssinante . '_' . substr(md5(uniqid('', true)), 0, 6) . '.pdf';

$pdf->writeHTML($htmlConteudo, true, false, true, false, '');
$yBodyEnd = (float)$pdf->GetY();
$pageBodyEnd = (int)$pdf->getPage();
$pdf->writeHTML($assinaturaHtml, true, false, true, false, '');
$pageSignature = (int)$pdf->getPage();
$ySignaturaStart = ($pageSignature > $pageBodyEnd)
    ? (float)$pdf->GetTopMargin()
    : $yBodyEnd;
$pdf->Output($pathOriginalTemp, 'F');

$assinado = contratoAssinarPdfComDirigente(
    $pdo,
    $BASE_para_PATH,
    $BASE_para_URL,
    $pathOriginalTemp,
    $nomeArquivoFinal,
    $nomeDocumentoContrato,
    $idAssinante,
    $nomeAssinante,
    $responsavelSistema,
    $ySignaturaStart
);

$pathSaida = $assinado['ok'] ? (string)$assinado['path'] : $pathOriginalTemp;
if (!is_file($pathSaida)) {
    die("<div class='alert alert-danger'>Erro ao gerar contrato assinado.</div>");
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($nomeArquivoFinal) . '"');
header('Content-Length: ' . filesize($pathSaida));
readfile($pathSaida);
exit;
