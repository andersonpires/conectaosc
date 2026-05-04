<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];

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

class RelatorioCustomPdf extends TCPDF
{
    public string $logoSistemaPath = '';
    public float $logoHeightMm = 20;
    public float $headerTopMm = 8;
    public float $headerLineGapMm = 2;

    public function Header()
    {
        $logoTop = $this->headerTopMm;

        if ($this->logoSistemaPath !== '' && is_file($this->logoSistemaPath)) {
            $this->Image($this->logoSistemaPath, 15, $logoTop, 0, $this->logoHeightMm, '', '', 'T', false, 300);
        }

        $lineY = $logoTop + $this->logoHeightMm + $this->headerLineGapMm;
        $this->Line(15, $lineY, $this->getPageWidth() - 15, $lineY);
    }

    public function Footer()
    {
    }
}

$curso = $_GET['curso'] ?? '';
$turma = $_GET['turma'] ?? '';
$matriculasAtivas = (!empty($_GET['matriculasAtivas']) && $_GET['matriculasAtivas'] !== '0') ? 1 : 0;
$filtroAtivos = (!empty($_GET['filtroAtivos']) && $_GET['filtroAtivos'] !== '0') ? 1 : 0;
$interesses = $_GET['interesses'] ?? [];
$colunasSelecionadas = $_GET['colunas'] ?? [];

if (!is_array($interesses)) {
    $interesses = [$interesses];
}

if (!is_array($colunasSelecionadas) || empty($curso) || empty($colunasSelecionadas)) {
    echo '<script>alert("Parâmetros inválidos ou nenhum campo selecionado."); window.close();</script>';
    exit;
}

$service = new RelatorioService(new RelatorioRepository());
$payload = [
    'curso' => $curso,
    'turma' => $turma,
    'matriculasAtivas' => $matriculasAtivas,
    'filtroAtivos' => $filtroAtivos,
    'interesses' => $interesses,
    'colunas' => $colunasSelecionadas,
];

$dados = $service->customAlunos($payload);
if (empty($dados)) {
    echo '<script>alert("Nenhum dado encontrado para os critérios selecionados."); window.close();</script>';
    exit;
}

$config = $pdo->query('SELECT LogoImpressao FROM tbConfig LIMIT 1')->fetch(PDO::FETCH_ASSOC);
$logoSistema = (string) ($config['LogoImpressao'] ?? 'logos/LogoImpressao.jpg');
if (trim($logoSistema) === '') {
    $logoSistema = 'logos/LogoImpressao.jpg';
}
$logoSistemaPath = resolveProjectLogoPath($logoSistema, $BASE_para_PATH);

$totalCols = 1 + count($colunasSelecionadas);
$orientacao = ($totalCols > 7) ? 'L' : 'P';

$weightMap = [
    'Nome' => 2.2,
    'Endereco' => 2.8,
    'Bairro' => 1.4,
    'Cidade' => 1.3,
    'NomeCurso' => 1.8,
    'NomeTurma' => 1.7,
    'Telefone' => 1.3,
    'WhatsApp' => 1.3,
    'Nascimento' => 1.1,
    'Email' => 2.0,
];

$weights = [];
$sumWeights = 0.0;
foreach ($colunasSelecionadas as $col) {
    $w = (float) ($weightMap[$col] ?? 1.0);
    $weights[] = $w;
    $sumWeights += $w;
}
if ($sumWeights <= 0) {
    $sumWeights = max(1.0, (float) count($colunasSelecionadas));
}

if (ob_get_level() === 0) {
    ob_start();
}

$pdf = new RelatorioCustomPdf($orientacao, 'mm', 'A4', true, 'UTF-8', false);
$pdf->logoSistemaPath = $logoSistemaPath;
$pdf->SetCreator('Conecta OSC');
$pdf->SetAuthor('Conecta OSC');
$pdf->SetTitle('Relatório Customizado');
$pdf->SetMargins(15, 40, 15);
$pdf->SetHeaderMargin(0);
$pdf->SetAutoPageBreak(true, 12);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 8);

$usableWidth = $pdf->getPageWidth() - 30;
$orderWidth = 12.0;
$remainingWidth = max(40.0, $usableWidth - $orderWidth);

$colPcts = [];
foreach ($weights as $w) {
    $cw = $remainingWidth * ($w / $sumWeights);
    $colPcts[] = round(($cw / $usableWidth) * 100, 2);
}
$orderPct = round(($orderWidth / $usableWidth) * 100, 2);

$html = '<h2 style="text-align:center;">Relatório Customizado</h2>';
$html .= '<table border="1" cellpadding="3" cellspacing="0" width="100%"><thead><tr>';
$html .= '<th width="' . $orderPct . '%" align="center"><b>Ordem</b></th>';

foreach ($colunasSelecionadas as $idx => $coluna) {
    $html .= '<th width="' . $colPcts[$idx] . '%"><b>' . htmlspecialchars((string) $coluna, ENT_QUOTES, 'UTF-8') . '</b></th>';
}

$html .= '</tr></thead><tbody>';

foreach ($dados as $index => $linha) {
    $html .= '<tr>';
    $html .= '<td width="' . $orderPct . '%" align="center">' . ($index + 1) . '</td>';

    foreach ($colunasSelecionadas as $idx => $coluna) {
        $valor = $linha[$coluna] ?? '';
        if (is_array($valor) || is_object($valor)) {
            $valor = json_encode($valor, JSON_UNESCAPED_UNICODE);
        }

        $texto = trim((string) $valor);
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? '';
        $html .= '<td width="' . $colPcts[$idx] . '%">' . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '</td>';
    }

    $html .= '</tr>';
}

$html .= '</tbody></table>';

$pdf->writeHTML($html, true, false, true, false, '');

while (ob_get_level() > 0) {
    ob_end_clean();
}

$pdf->Output('relatorio_customizado.pdf', 'I');
exit;

