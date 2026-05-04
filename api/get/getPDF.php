<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];

if (!isset($BASE_para_PATH)) {
    echo '<script>alert("Sessão inválida."); window.close();</script>';
    exit;
}

require_once $BASE_para_PATH . '/api/repositories/RelatorioRepository.php';
require_once $BASE_para_PATH . '/api/services/RelatorioService.php';
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';
require_once $BASE_para_PATH . '/api/lib/tcpdf/tcpdf.php';

use BackEnd\Repositories\RelatorioRepository;
use BackEnd\Services\RelatorioService;

function resolveProjectLogoPath($logoRaw, $basePath)
{
    if (!is_string($logoRaw)) {
        return '';
    }

    $logoRaw = trim(str_replace('\\', '/', $logoRaw));
    if ($logoRaw === '') {
        return '';
    }

    if (preg_match('/^https?:\/\//i', $logoRaw)) {
        return $logoRaw;
    }

    $basePath = rtrim($basePath, '/\\');
    $assetsImgPath = '';
    if (isset($GLOBALS['runtime']['assets_img_path'])) {
        $assetsImgPath = rtrim((string) $GLOBALS['runtime']['assets_img_path'], '/\\');
    }

    $candidates = [
        $basePath . '/' . ltrim($logoRaw, '/'),
        dirname($basePath) . '/' . ltrim($logoRaw, '/'),
        $basePath . '/app/assets/img/' . ltrim($logoRaw, '/'),
        $basePath . '/app/assets/img/logos/' . ltrim($logoRaw, '/'),
        $basePath . '/app/assets/img/sistema/' . ltrim($logoRaw, '/'),
        $basePath . '/app/assets/img/fotos/' . ltrim($logoRaw, '/'),
    ];

    if ($assetsImgPath !== '') {
        $candidates[] = $assetsImgPath . '/' . ltrim($logoRaw, '/');
        $candidates[] = $assetsImgPath . '/logos/' . ltrim($logoRaw, '/');
        $candidates[] = $assetsImgPath . '/sistema/' . ltrim($logoRaw, '/');
        $candidates[] = $assetsImgPath . '/fotos/' . ltrim($logoRaw, '/');
    }

    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return '';
}

class RelatorioMensalPdf extends TCPDF
{
    public string $logoSistemaPath = '';
    public string $logoProjetoPath = '';
    public float $logoHeightMm = 20;
    public float $headerTopMm = 8;
    public float $headerLineGapMm = 2;
    public float $tableHeaderHeightMm = 22;

    public function Header()
    {
        $logoTop = $this->headerTopMm;

        if ($this->logoSistemaPath !== '' && is_file($this->logoSistemaPath)) {
            $this->Image($this->logoSistemaPath, 15, $logoTop, 0, $this->logoHeightMm, '', '', 'T', false, 300);
        }

        if ($this->logoProjetoPath !== '' && (is_file($this->logoProjetoPath) || preg_match('/^https?:\/\//i', $this->logoProjetoPath))) {
            $rightX = $this->getPageWidth() - 15 - 35;
            $this->Image($this->logoProjetoPath, $rightX, $logoTop, 0, $this->logoHeightMm, '', '', 'T', false, 300);
        }

        $lineY = $logoTop + $this->logoHeightMm + $this->headerLineGapMm;
        $this->Line(15, $lineY, $this->getPageWidth() - 15, $lineY);
    }

    public function Footer()
    {
    }

    private function drawRotatedHeaderCell(float $w, float $h, string $label): void
    {
        $x = $this->GetX();
        $y = $this->GetY();

        $this->Rect($x, $y, $w, $h);
        $this->SetFont('helvetica', 'B', 6.5);
        $this->StartTransform();
        $this->Rotate(-90, $x + ($w / 2), $y + ($h / 2));
        $this->SetXY($x - (($h - $w) / 2), $y + (($h - $w) / 2));
        $this->Cell($h, $w, $label, 0, 0, 'C');
        $this->StopTransform();
        $this->SetXY($x + $w, $y);
    }

    public function drawTableHeader(array $dias, array $dayLabels, float $wNum, float $wNome, float $wDia): void
    {
        $startX = $this->GetX();
        $startY = $this->GetY();
        $this->SetFont('helvetica', 'B', 8);
        $headerHeight = $this->tableHeaderHeightMm;
        $this->Cell($wNum, $headerHeight, 'Nº', 1, 0, 'C');
        $this->Cell($wNome, $headerHeight, 'Nome dos beneficiários', 1, 0, 'L');

        foreach ($dias as $idx => $dia) {
            $label = (string) ($dayLabels[$idx] ?? $dia);
            $this->drawRotatedHeaderCell($wDia, $headerHeight, $label);
        }
        $this->SetXY($startX, $startY + $headerHeight);
    }

    public function drawAlunoRow(int $indice, string $nome, array $dias, array $tabelaAluno, float $wNum, float $wNome, float $wDia): float
    {
        $baseHeight = 6.0;
        $nomeHeight = $this->getStringHeight($wNome, $nome, false, true, '', 1, false);
        $rowHeight = max($baseHeight, $nomeHeight + 1.2);

        $x = $this->GetX();
        $y = $this->GetY();

        $this->Cell($wNum, $rowHeight, (string) $indice, 1, 0, 'C');

        $this->MultiCell(
            $wNome,
            $rowHeight,
            $nome,
            1,
            'L',
            false,
            0,
            '',
            '',
            true,
            0,
            false,
            true,
            $rowHeight,
            'M'
        );

        foreach ($dias as $dia) {
            $status = (string) ($tabelaAluno[$dia] ?? 'NA');
            $this->Cell($wDia, $rowHeight, $status, 1, 0, 'C');
        }

        $this->SetXY($x, $y + $rowHeight);
        return $rowHeight;
    }
}

$curso = $_GET['curso'] ?? '';
$turma = $_GET['turma'] ?? '';
$mesAno = $_GET['mesAno'] ?? '';

if (!str_contains($mesAno, '-')) {
    echo '<script>alert("Mês/Ano inválido."); window.close();</script>';
    exit;
}

$service = new RelatorioService(new RelatorioRepository());
$data = $service->frequenciaMensal($_GET, true);

if (empty($data)) {
    echo '<script>alert("Nenhum dado encontrado para os critérios selecionados."); window.close();</script>';
    exit;
}

$tabela = [];
$alunos = [];
$dias = [];

$cursoNome = (string) ($data[0]['NomeCurso'] ?? '');
$cursoDuracao = (string) ($data[0]['Duracao'] ?? '');
$cursoTipo = (string) ($data[0]['Tipo'] ?? '');
$cursoCH = (string) ($data[0]['CargaHoraria'] ?? '');
$cursoTermo = (string) ($data[0]['Termo'] ?? '');
$nomeProjeto = (string) ($data[0]['NomeProjeto'] ?? '');
$logoProjeto = (string) ($data[0]['LogoProjeto'] ?? '');
$turmaNome = ($turma !== 'todas') ? (string) ($data[0]['NomeTurma'] ?? '') : 'Todas';

foreach ($data as $row) {
    $aluno = (string) ($row['Aluno'] ?? '');
    $dia = (string) ($row['Dia'] ?? '');
    $status = ((int) ($row['presenca'] ?? 0) === 1) ? 'P' : (((int) ($row['falta'] ?? 0) === 1) ? 'F' : (((int) ($row['faltajust'] ?? 0) === 1) ? 'FJ' : ''));

    if (!isset($tabela[$aluno])) {
        $tabela[$aluno] = [];
        $alunos[] = $aluno;
    }

    if (!in_array($dia, $dias, true)) {
        $dias[] = $dia;
    }

    $tabela[$aluno][$dia] = $status;
}

sort($dias, SORT_NUMERIC);

$mesAnoPartes = explode('-', (string) $mesAno);
$anoRelatorio = isset($mesAnoPartes[0]) ? (int) $mesAnoPartes[0] : 0;
$mesRelatorio = isset($mesAnoPartes[1]) ? (int) $mesAnoPartes[1] : 0;
$dayLabels = [];
foreach ($dias as $dia) {
    $dayLabels[] = sprintf('%02d/%02d/%02d', (int) $dia, $mesRelatorio, $anoRelatorio % 100);
}

$config = $pdo->query('SELECT LogoImpressao FROM tbConfig LIMIT 1')->fetch(PDO::FETCH_ASSOC);
$logoSistema = (string) ($config['LogoImpressao'] ?? 'logos/LogoImpressao.jpg');
if (trim($logoSistema) === '') {
    $logoSistema = 'logos/LogoImpressao.jpg';
}

$logoSistemaPath = resolveProjectLogoPath($logoSistema, $BASE_para_PATH);
$logoProjetoPath = resolveProjectLogoPath($logoProjeto, $BASE_para_PATH);

$margin = 15;
$wNum = 10.0;
$diaCount = max(1, count($dias));

$portraitPageWidth = 210.0;
$portraitUsable = $portraitPageWidth - ($margin * 2);
$portraitNome = 70.0;
$portraitDia = ($portraitUsable - $wNum - $portraitNome) / $diaCount;

if ($portraitDia >= 4.8) {
    $orientation = 'P';
    $pageWidth = $portraitPageWidth;
    $usableWidth = $portraitUsable;
    $wNome = $portraitNome;
    $wDia = $portraitDia;
} else {
    $orientation = 'L';
    $pageWidth = 297.0;
    $usableWidth = $pageWidth - ($margin * 2);
    $wNome = ($diaCount > 22) ? 70.0 : 85.0;
    $wDia = ($usableWidth - $wNum - $wNome) / $diaCount;

    if ($wDia < 4.2) {
        $wNome = max(55.0, $usableWidth - $wNum - ($diaCount * 4.2));
        $wDia = ($usableWidth - $wNum - $wNome) / $diaCount;
    }
}

if (ob_get_level() === 0) {
    ob_start();
}

$pdf = new RelatorioMensalPdf($orientation, 'mm', 'A4', true, 'UTF-8', false);
$pdf->logoSistemaPath = $logoSistemaPath;
$pdf->logoProjetoPath = $logoProjetoPath;
$pdf->SetCreator('Conecta OSC');
$pdf->SetAuthor('Conecta OSC');
$pdf->SetTitle('Relatório de Frequência Mensal');
$pdf->SetMargins($margin, 40, $margin);
$pdf->SetHeaderMargin(0);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

$infoHtml = '
<h2 style="text-align:center;">Relatório de Frequência</h2>
<table cellpadding="4" border="1">
  <tr>
    <td><b>Curso</b><br>' . htmlspecialchars($cursoNome, ENT_QUOTES, 'UTF-8') . '</td>
    <td><b>Projeto</b><br>' . htmlspecialchars($nomeProjeto, ENT_QUOTES, 'UTF-8') . '</td>
    <td><b>Turma</b><br>' . htmlspecialchars($turmaNome, ENT_QUOTES, 'UTF-8') . '</td>
  </tr>
  <tr>
    <td><b>Tipo</b><br>' . htmlspecialchars($cursoTipo, ENT_QUOTES, 'UTF-8') . '</td>
    <td><b>Duração</b><br>' . htmlspecialchars($cursoDuracao, ENT_QUOTES, 'UTF-8') . '</td>
    <td><b>Carga Horária</b><br>' . htmlspecialchars($cursoCH, ENT_QUOTES, 'UTF-8') . '</td>
  </tr>
  <tr>
    <td colspan="3"><b>Termo</b><br>' . htmlspecialchars($cursoTermo, ENT_QUOTES, 'UTF-8') . '</td>
  </tr>
</table>
<br>';

$pdf->SetFont('helvetica', '', 10);
$pdf->writeHTML($infoHtml, true, false, true, false, '');

$pdf->drawTableHeader($dias, $dayLabels, $wNum, $wNome, $wDia);
$pdf->SetFont('helvetica', '', 8);

$bottomLimit = $pdf->getPageHeight() - 15;
$idx = 1;

foreach ($alunos as $aluno) {
    $nomeHeight = $pdf->getStringHeight($wNome, $aluno, false, true, '', 1, false);
    $rowHeight = max(6.0, $nomeHeight + 1.2);

    if ($pdf->GetY() + $rowHeight > $bottomLimit) {
        $pdf->AddPage();
        $pdf->drawTableHeader($dias, $dayLabels, $wNum, $wNome, $wDia);
        $pdf->SetFont('helvetica', '', 8);
    }

    $pdf->drawAlunoRow($idx, $aluno, $dias, $tabela[$aluno] ?? [], $wNum, $wNome, $wDia);
    $idx++;
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

$pdf->Output('relatorio_frequencia.pdf', 'I');
exit;

