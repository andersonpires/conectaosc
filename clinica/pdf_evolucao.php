<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_name('PHPSESSID3');
    session_start();
}

date_default_timezone_set('America/Sao_Paulo');

if (empty($_SESSION['Cod']) || (int)($_SESSION['profissional_saude'] ?? 0) !== 1) {
    header('Content-Type: text/html; charset=utf-8');
    die('<div class="alert alert-danger">Acesso restrito a profissionais de saúde.</div>');
}

$basePath = rtrim(str_replace('\\', '/', (string)($_SESSION['BASE_para_PATH'] ?? dirname(__DIR__))), '/');
$conexaoPath = $basePath . '/api/conectabd/conexao.php';
if (!is_file($conexaoPath)) {
    $conexaoPath = $basePath . '/conectabd/conexao.php';
}
require $conexaoPath;

$tcpdfPath = $basePath . '/api/lib/tcpdf/tcpdf.php';
if (!is_file($tcpdfPath)) {
    $tcpdfPath = $basePath . '/lib/tcpdf/tcpdf.php';
}
require $tcpdfPath;

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Content-Type: text/html; charset=utf-8');
    die('<div class="alert alert-danger">ID inválido.</div>');
}

$stmt = $pdo->prepare("
    SELECT e.*, al.Nome AS paciente_nome, al.Nascimento AS paciente_nascimento,
           CONCAT(u.Nome, ' ', u.Sobrenome) AS profissional_nome,
           esp.nome AS especialidade_nome
    FROM tb_evolucao_clinica e
    JOIN tbAluno al ON al.IdUsuario = e.aluno_id
    JOIN tbUser u ON u.IdColaborador = e.profissional_id
    LEFT JOIN tb_especialidade esp ON esp.id = e.especialidade_id
    WHERE e.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header('Content-Type: text/html; charset=utf-8');
    die('<div class="alert alert-danger">Evolução não encontrada.</div>');
}

$logoRow = $pdo->query("SELECT LogoImpressao FROM tbConfig LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$logoNome = trim((string)($logoRow['LogoImpressao'] ?? ''));
$logoPath = '';
foreach ([
    $basePath . '/app/assets/img/logos/' . $logoNome,
    $basePath . '/app/assets/img/sistema/' . $logoNome,
    $basePath . '/app/assets/img/logos/logoimpressao.png',
    $basePath . '/app/assets/img/sistema/logo.png',
] as $candidate) {
    if ($logoNome === '' && !str_contains($candidate, 'logoimpressao') && !str_contains($candidate, 'logo.png')) {
        continue;
    }
    if (is_file($candidate)) {
        $logoPath = $candidate;
        break;
    }
}

class EvolucaoPdf extends TCPDF
{
    public string $logoPath = '';

    public function Header()
    {
        if ($this->logoPath !== '' && is_file($this->logoPath)) {
            $this->Image($this->logoPath, 15, 8, 0, 18);
        }
        $this->Line(15, 29, $this->getPageWidth() - 15, 29);
    }

    public function Footer()
    {
        $this->SetY(-18);
        $this->SetFont('helvetica', '', 8);
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

$pdf = new EvolucaoPdf();
$pdf->logoPath = $logoPath;
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);
$pdf->SetMargins(15, 36, 15);
$pdf->SetAutoPageBreak(true, 20);
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Evolução Clínica', 0, 1, 'C');
$pdf->Ln(2);
$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(0, 6, 'Paciente: ' . (string)$row['paciente_nome'], 0, 'L');
$pdf->MultiCell(0, 6, 'Data/Hora: ' . date('d/m/Y', strtotime((string)$row['data_evolucao'])) . ' às ' . substr((string)$row['hora_evolucao'], 0, 5), 0, 'L');
$pdf->MultiCell(0, 6, 'Profissional: ' . (string)$row['profissional_nome'], 0, 'L');
$pdf->MultiCell(0, 6, 'Especialidade: ' . (string)($row['especialidade_nome'] ?? ''), 0, 'L');
$pdf->Ln(4);
$pdf->SetFont('dejavusans', '', 10);
$conteudo = (string)($row['conteudo'] ?? '');
$html = strip_tags($conteudo) === $conteudo ? nl2br(htmlspecialchars($conteudo, ENT_QUOTES, 'UTF-8')) : $conteudo;
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(10);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 8, (string)$row['profissional_nome'], 0, 1, 'R');
$pdf->Cell(0, 6, (string)($row['especialidade_nome'] ?? ''), 0, 1, 'R');

$pdf->Output('evolucao-' . $id . '.pdf', 'I');
exit;
