<?php
session_set_cookie_params(['httponly' => true]);
ini_set('session.gc_maxlifetime', 86400);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?redirect=$redirect_url");
    exit();
}

require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';
require_once $_SESSION['BASE_PATH'] . '/lib/tcpdf/tcpdf.php';

$idTurma = isset($_GET['turma']) ? intval($_GET['turma']) : 0;

if ($idTurma <= 0) {
    die("<div class='alert alert-danger'>Parametros invalidos.</div>");
}

$sql = $pdo->prepare("
    SELECT a.*,
           c.NomeCurso,
           t.NomeTurma,
           m.IdMatricula,
           m.vData,
           p.LogoProjeto,
           p.TermosContrato
      FROM tbMatricula m
      JOIN tbAluno a ON m.IdUsuario = a.IdUsuario
      JOIN tbCurso c ON m.IdCurso = c.IdCurso
      JOIN tbTurma t ON m.IdTurma = t.IdTurma
      LEFT JOIN tbProjeto p ON c.IdProjeto = p.IdProjeto
     WHERE m.IdTurma = ?
       AND m.Habilitado = 1
     ORDER BY a.Nome
");
$sql->execute([$idTurma]);
$lista = $sql->fetchAll(PDO::FETCH_ASSOC);

if (!$lista) {
    die("<div class='alert alert-danger'>Nenhum aluno ativo encontrado nesta turma.</div>");
}

function resolveProjectLogoPath($logoRaw, $basePath) {
    if (!is_string($logoRaw)) {
        return '';
    }

    $logoRaw = trim($logoRaw);
    if ($logoRaw === '') {
        return '';
    }

    if (preg_match('/^https?:\\/\\//i', $logoRaw)) {
        return $logoRaw;
    }

    $logoRaw = str_replace('\\', '/', $logoRaw);
    $basePath = rtrim($basePath, '/\\');

    $candidates = [];
    $candidates[] = $basePath . '/' . ltrim($logoRaw, '/');
    $candidates[] = $basePath . '/assets/img/logos/' . ltrim($logoRaw, '/');
    $candidates[] = $basePath . '/assets/img/sistema/' . ltrim($logoRaw, '/');
    $candidates[] = $basePath . '/assets/img/fotos/' . ltrim($logoRaw, '/');

    foreach ($candidates as $path) {
        if (is_file($path)) {
            return $path;
        }
    }

    return '';
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
        $this->Cell(0, 10, 'Pagina ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

$config = $pdo->query("SELECT LogoImpressao, MetaAuthor FROM tbConfig LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$logoSistema = $config['LogoImpressao'] ?? '';
$responsavelSistema = $config['MetaAuthor'] ?? '';
$logoSistemaPath = resolveProjectLogoPath($logoSistema, $_SESSION['BASE_PATH']);

$nomeTurma = $lista[0]['NomeTurma'] ?? '';
$nomeDocumento = trim($nomeTurma) !== '' ? ("Contratos - " . $nomeTurma) : "Contratos";

$pdf = new ContratoPDF();
$pdf->logoSistemaPath = $logoSistemaPath;
$pdf->SetCreator('Conecta OSC');
$pdf->SetAuthor('Conecta OSC');
$pdf->SetTitle($nomeDocumento);
$pdf->SetMargins(15, 40, 15);
$pdf->SetHeaderMargin(0);

foreach ($lista as $dados) {
    $logoPath = resolveProjectLogoPath($dados['LogoProjeto'] ?? '', $_SESSION['BASE_PATH']);

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
    $termosContrato = $dados['TermosContrato'] ?? '';

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
        $responsavelHtml = '
<h4>Responsavel</h4>
<table cellpadding="4" border="1">
    ' . $responsavelRows . '
</table>';
    }

    $termosHtml = '';
    $termosContrato = trim((string) $termosContrato);
    if ($termosContrato !== '') {
        $termosHtml = '
' . html_entity_decode($termosContrato);
    }

    $assinaturaHtml = '';
    if (trim((string) $responsavelSistema) !== '') {
        $assinaturaHtml .= '
<table cellpadding="0" cellspacing="0" border="0">
    <tr><td style="height:20mm;"></td></tr>
</table>
<p style="text-align:center;">______________________________________________<br>' . htmlspecialchars($responsavelSistema) . '</p>
';
    }
    $assinaturaHtml .= '
<table cellpadding="0" cellspacing="0" border="0">
    <tr><td style="height:20mm;"></td></tr>
</table>
<p style="text-align:center;">______________________________________________<br>' . htmlspecialchars($nome) . '</p>
';

    $html = '
<h2 style="text-align:center;"><b>TERMO DE RESPONSABILIDADE E COMPROMISSO</b></h2>

<h4>Dados do Beneficiario</h4>
<table cellpadding="4" border="1">
    <tr><td><b>Nome</b></td><td>' . htmlspecialchars($nome) . '</td></tr>
    <tr><td><b>Data de nascimento</b></td><td>' . htmlspecialchars($nascimento) . '</td></tr>
    <tr><td><b>CPF</b></td><td>' . htmlspecialchars($cpf) . '</td></tr>
    <tr><td><b>Endereco</b></td><td>' . htmlspecialchars($endereco) . '</td></tr>
    <tr><td><b>Bairro</b></td><td>' . htmlspecialchars($bairro) . '</td></tr>
    <tr><td><b>Cidade/UF</b></td><td>' . htmlspecialchars(trim($cidade . ' / ' . $uf)) . '</td></tr>
    <tr><td><b>Telefone</b></td><td>' . htmlspecialchars($telefone) . '</td></tr>
    <tr><td><b>WhatsApp</b></td><td>' . htmlspecialchars($whatsapp) . '</td></tr>
</table>
' . $responsavelHtml . $termosHtml . $assinaturaHtml;

    $pdf->logoPath = $logoPath;
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 11);
    $pdf->writeHTML($html, true, false, true, false, '');
}

$pdf->Output("contratos_turma_" . $idTurma . ".pdf", 'I');
exit;
