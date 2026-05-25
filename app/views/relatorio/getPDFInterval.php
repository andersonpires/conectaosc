<?php
declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
$invertextoApiToken = bootstrap_invertexto_api_token($BASE_para_PATH);

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
}

require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
require_once $BASE_para_PATH . '/api/lib/tcpdf/tcpdf.php';
require_once $BASE_para_PATH . '/api/lib/tcpdf/fpdi/autoload.php';

use setasign\Fpdi\Tcpdf\Fpdi;

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo 'Erro de conexão com o banco de dados.';
    exit;
}

function boolParam(string $name): bool
{
    return (string)($_GET[$name] ?? '0') === '1';
}

function resolverLogoPath(string $logoRaw, string $basePath): string
{
    $logoRaw = trim($logoRaw);
    if ($logoRaw === '') {
        return '';
    }
    if (preg_match('/^https?:\\/\\//i', $logoRaw)) {
        return $logoRaw;
    }

    $logoRaw = str_replace('\\', '/', $logoRaw);
    $basePath = rtrim(str_replace('\\', '/', $basePath), '/');
    $assetsImgPath = rtrim(str_replace('\\', '/', bootstrap_assets_img_path()), '/');

    $candidates = [
        $basePath . '/' . ltrim($logoRaw, '/'),
        $assetsImgPath . '/' . ltrim($logoRaw, '/'),
        $assetsImgPath . '/logos/' . ltrim($logoRaw, '/'),
        $assetsImgPath . '/sistema/' . ltrim($logoRaw, '/'),
        $assetsImgPath . '/fotos/' . ltrim($logoRaw, '/'),
    ];

    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return '';
}

function assinaturaResolveAssinaturaDigitalPublicUrl(string $baseUrl): string
{
    $configured = trim((string)($_SESSION['BASE_PUBLIC_URL'] ?? (getenv('APP_PUBLIC_BASE_URL') ?: '')));
    if ($configured !== '') {
        return rtrim($configured, '/') . '/assinaturadigital';
    }

    $parsed = parse_url(trim($baseUrl));
    $basePath = preg_match('#^https?://#i', trim($baseUrl))
        ? (string)($parsed['path'] ?? '')
        : trim($baseUrl);
    $basePath = '/' . ltrim(str_replace('\\', '/', $basePath), '/');
    $basePath = rtrim($basePath, '/');
    $basePath = $basePath === '' ? '/conecta' : $basePath;

    $host = (string)($_SERVER['HTTP_HOST'] ?? ($parsed['host'] ?? ''));
    $host = strtolower($host);
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        ? 'https'
        : (string)($parsed['scheme'] ?? 'http');
    $port = isset($parsed['port']) ? ':' . (int)$parsed['port'] : '';

    if ($host === '' || str_contains($host, 'localhost') || str_starts_with($host, '127.0.0.1')) {
        return 'http://localhost' . $basePath . '/assinaturadigital';
    }

    return $scheme . '://' . $host . $port . $basePath . '/assinaturadigital';
}

function assinaturaResolvePublicBaseUrl(string $baseUrl): string
{
    $configured = trim((string)($_SESSION['BASE_PUBLIC_URL'] ?? (getenv('APP_PUBLIC_BASE_URL') ?: '')));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $parsed = parse_url(trim($baseUrl));
    if (preg_match('#^https?://#i', trim($baseUrl))) {
        $scheme = (string)($parsed['scheme'] ?? 'http');
        $host = (string)($parsed['host'] ?? '');
        $port = isset($parsed['port']) ? ':' . (int)$parsed['port'] : '';
        $path = '/' . ltrim((string)($parsed['path'] ?? ''), '/');
        $path = rtrim($path, '/');
        if ($host !== '') {
            return $scheme . '://' . $host . $port . $path;
        }
    }

    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $path = '/' . ltrim(trim($baseUrl), '/');
    $path = rtrim($path, '/');

    if ($host !== '') {
        return $scheme . '://' . $host . $path;
    }

    return $path !== '' ? $path : '/conecta';
}

function assinaturaGerarCodigoValidacaoCurto(PDO $pdo, int $timestampAssinatura): string
{
    $alfabeto = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    for ($tentativa = 0; $tentativa < 20; $tentativa++) {
        $prefixo = '';
        for ($i = 0; $i < 2; $i++) {
            $prefixo .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }

        $codigo = $prefixo . $timestampAssinatura;
        $stmt = $pdo->prepare('SELECT 1 FROM tbpdf_assinado WHERE AssinaturaBase64 = ? LIMIT 1');
        $stmt->execute([$codigo]);
        if (!$stmt->fetchColumn()) {
            return $codigo;
        }
    }

    return 'ZZ' . $timestampAssinatura;
}

function assinaturaGerarNomeArquivoFinal(string $diretorioAssinados, int $idColaborador): string
{
    $timestampBase = time();
    $tentativa = 0;

    do {
        $timestampAtual = $timestampBase + $tentativa;
        $nome = 'frequencia_intervalo_' . $timestampAtual . '_' . $idColaborador . '.pdf';
        $path = rtrim($diretorioAssinados, '/\\') . '/' . $nome;
        $tentativa++;
    } while (file_exists($path) && $tentativa < 10);

    return $nome;
}

function safeText(string $value, int $max = 120): string
{
    $value = trim(preg_replace('/\\s+/u', ' ', $value) ?? '');
    if ($max > 0) {
        $value = mb_substr($value, 0, $max, 'UTF-8');
    }
    return $value;
}

function buildDayColumnWidths(float $usableWidth, float $fixedWidth, int $dayCount): array
{
    if ($dayCount <= 0) {
        return [];
    }

    $remainingWidth = max(0.0, $usableWidth - $fixedWidth);
    $baseWidth = round($remainingWidth / $dayCount, 4);
    $widths = [];
    $consumedWidth = 0.0;

    for ($index = 0; $index < $dayCount; $index++) {
        if ($index === ($dayCount - 1)) {
            $widths[] = round(max(0.0, $remainingWidth - $consumedWidth), 4);
            continue;
        }

        $widths[] = $baseWidth;
        $consumedWidth += $baseWidth;
    }

    return $widths;
}

$curso = trim((string)($_GET['curso2'] ?? ''));
$turma = trim((string)($_GET['turma2'] ?? ''));
$dataInicio = trim((string)($_GET['dataInicio'] ?? ''));
$dataFim = trim((string)($_GET['dataFim'] ?? ''));
$habilitado = (int)($_GET['habilitado'] ?? 0);
// Mantido por compatibilidade com a URL atual, mas o layout em PDF segue o padrão antigo.
$incluirTurma = boolParam('turmaInterval');
$assinar = boolParam('assinar');
$incluirCargo = boolParam('incluir_cargo');
$cargoPersonalizado = safeText((string)($_GET['cargo_personalizado'] ?? ''), 120);

$dataInicioObj = DateTimeImmutable::createFromFormat('Y-m-d', $dataInicio) ?: null;
$dataFimObj = DateTimeImmutable::createFromFormat('Y-m-d', $dataFim) ?: null;

if (!$dataInicioObj || !$dataFimObj) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<script>alert('Datas inválidas.');window.close();</script>";
    exit;
}

$dataInicioDb = $dataInicioObj->format('Y-m-d');
$dataFimDb = $dataFimObj->format('Y-m-d');
$dataInicioFormatada = $dataInicioObj->format('d/m/Y');
$dataFimFormatada = $dataFimObj->format('d/m/Y');

$sql = "SELECT
            a.IdUsuario AS IdAluno,
            a.Nome AS Aluno,
            c.Dia,
            c.Mes,
            c.Ano,
            c.Data,
            c.presenca,
            c.falta,
            c.faltajust,
            cu.NomeCurso,
            cu.Duracao,
            cu.Tipo,
            cu.CargaHoraria,
            cu.Termo,
            t.NomeTurma,
            p.NomeProjeto,
            p.LogoProjeto
        FROM tbChamada c
        INNER JOIN tbAluno a ON c.IdAluno = a.IdUsuario
        INNER JOIN tbCurso cu ON c.IdCurso = cu.IdCurso
        INNER JOIN tbTurma t ON c.IdTurma = t.IdTurma
        LEFT JOIN tbProjeto p ON cu.IdProjeto = p.IdProjeto";

if ($habilitado === 1) {
    $sql .= " INNER JOIN tbMatricula m ON c.IdMatricula = m.IdMatricula
              AND a.Habilitado = 1
              AND m.Habilitado = 1";
}

$sql .= " WHERE c.IdCurso = ?
          AND c.Data >= ?
          AND c.Data <= ?
          AND (c.presenca = 1 OR c.falta = 1 OR c.faltajust = 1)";

$params = [$curso, $dataInicioDb, $dataFimDb];
if ($turma !== '' && strtolower($turma) !== 'todas') {
    $sql .= " AND c.IdTurma = ?";
    $params[] = $turma;
}

$sql .= " ORDER BY a.Nome, c.Data";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($data)) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<script>alert('Nenhum dado encontrado para os critérios selecionados.');window.close();</script>";
    exit;
}

$config = $pdo->query('SELECT LogoImpressao FROM tbConfig LIMIT 1')->fetch(PDO::FETCH_ASSOC) ?: [];
$logoSistemaPath = resolverLogoPath((string)($config['LogoImpressao'] ?? ''), $BASE_para_PATH);
$logoProjetoPath = resolverLogoPath((string)($data[0]['LogoProjeto'] ?? ''), $BASE_para_PATH);

$alunos = [];
$alunosInfo = [];
$tabela = [];
$diasSet = [];

foreach ($data as $row) {
    $idAluno = (int)($row['IdAluno'] ?? 0);
    if ($idAluno <= 0) {
        continue;
    }

    $nomeAluno = safeText((string)($row['Aluno'] ?? ''), 160);
    $turmaNomeLinha = safeText((string)($row['NomeTurma'] ?? ''), 120);
    $diaKey = sprintf('%02d/%02d/%02d', (int)$row['Dia'], (int)$row['Mes'], (int)$row['Ano'] % 100);
    $diasSet[$diaKey] = true;

    if (!isset($alunosInfo[$idAluno])) {
        $alunosInfo[$idAluno] = [
            'nome' => $nomeAluno,
            'turma' => $turmaNomeLinha,
            'totalP' => 0,
            'totalF' => 0,
            'totalFJ' => 0,
        ];
        $alunos[] = $idAluno;
    } elseif ($alunosInfo[$idAluno]['turma'] === '' && $turmaNomeLinha !== '') {
        $alunosInfo[$idAluno]['turma'] = $turmaNomeLinha;
    }

    if ((int)$row['presenca'] === 1) {
        $status = 'P';
        $alunosInfo[$idAluno]['totalP']++;
    } elseif ((int)$row['falta'] === 1) {
        $status = 'F';
        $alunosInfo[$idAluno]['totalF']++;
    } elseif ((int)$row['faltajust'] === 1) {
        $status = 'FJ';
        $alunosInfo[$idAluno]['totalFJ']++;
    } else {
        $status = 'NA';
    }

    $tabela[$idAluno][$diaKey] = $status;
}

$dias = array_keys($diasSet);
usort($dias, static function (string $a, string $b): int {
    $dataA = DateTimeImmutable::createFromFormat('d/m/y', $a);
    $dataB = DateTimeImmutable::createFromFormat('d/m/y', $b);
    if ($dataA && $dataB) {
        return $dataA <=> $dataB;
    }
    return strcmp($a, $b);
});

if (empty($dias) || empty($alunos)) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<script>alert('Nenhum dado encontrado para os critérios selecionados.');window.close();</script>";
    exit;
}

$cursoNome = safeText((string)($data[0]['NomeCurso'] ?? ''), 140);
$cursoDuracao = safeText((string)($data[0]['Duracao'] ?? ''), 80);
$cursoTipo = safeText((string)($data[0]['Tipo'] ?? ''), 80);
$cursoCH = safeText((string)($data[0]['CargaHoraria'] ?? ''), 80);
$cursoTermo = safeText((string)($data[0]['Termo'] ?? ''), 80);
$turmaNome = ($turma !== '' && strtolower($turma) !== 'todas')
    ? safeText((string)($data[0]['NomeTurma'] ?? ''), 120)
    : 'Todas';
$nomeProjeto = safeText((string)($data[0]['NomeProjeto'] ?? ''), 120);

$orientation = 'P';
$margin = 10.0;
$pageWidth = 210.0;
$usableWidth = $pageWidth - ($margin * 2);

$wNum = 10.0;
$wNome = 70.0;
$wFixas = $wNum + $wNome;
$dayCount = count($dias);
$dayWidths = buildDayColumnWidths($usableWidth, $wFixas, $dayCount);
$wDia = $dayCount > 0 ? (($usableWidth - $wFixas) / $dayCount) : 0.0;

if ($wDia < 6.0) {
    $orientation = 'L';
    $pageWidth = 297.0;
    $usableWidth = $pageWidth - ($margin * 2);
    $wNome = 90.0;
    $wFixas = $wNum + $wNome;
    $dayWidths = buildDayColumnWidths($usableWidth, $wFixas, $dayCount);
    $wDia = $dayCount > 0 ? (($usableWidth - $wFixas) / $dayCount) : 0.0;
}

class RelatorioFrequenciaIntervaloPdf extends TCPDF
{
    public string $logoSistemaPath = '';
    public string $logoProjetoPath = '';
    public array $info = [];
    public array $dias = [];
    public float $wNum = 10.0;
    public float $wNome = 70.0;
    public float $wDia = 6.0;
    public array $dayWidths = [];
    public float $bodyStartY = 0.0;
    public bool $skipTableHeaderRow = false;

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

    private function splitTextoCurso(string $texto, int $limite): array
    {
        $texto = trim($texto);
        if ($texto === '' || mb_strlen($texto, 'UTF-8') <= $limite) {
            return [$texto];
        }

        $palavras = preg_split('/\s+/u', $texto) ?: [];
        $linha1 = '';
        $linha2 = '';
        foreach ($palavras as $palavra) {
            $palavra = trim((string)$palavra);
            if ($palavra === '') {
                continue;
            }
            $teste = $linha1 === '' ? $palavra : ($linha1 . ' ' . $palavra);
            if (mb_strlen($teste, 'UTF-8') <= $limite) {
                $linha1 = $teste;
                continue;
            }
            $linha2 = $linha2 === '' ? $palavra : ($linha2 . ' ' . $palavra);
        }

        if ($linha1 === '') {
            $linha1 = mb_substr($texto, 0, $limite, 'UTF-8');
            $linha2 = trim(mb_substr($texto, $limite, null, 'UTF-8'));
        }

        return [$linha1, $linha2];
    }

    public function Header(): void
    {
        $margin = 10.0;
        $topY = 8.0;
        $logoHeight = 18.0;

        if ($this->logoSistemaPath !== '') {
            $this->Image($this->logoSistemaPath, $margin, $topY, 0, $logoHeight, '', '', '', false, 300);
        }
        if ($this->logoProjetoPath !== '') {
            $rightX = $this->getPageWidth() - $margin - 30;
            $this->Image($this->logoProjetoPath, $rightX, $topY, 30, 0, '', '', '', false, 300);
        }

        $lineY = $topY + $logoHeight + 2;
        $this->Line($margin, $lineY, $this->getPageWidth() - $margin, $lineY);
        $this->SetY($lineY + 2);

        $this->SetFont('helvetica', 'B', 11);
        $this->Cell(0, 5, 'INSTITUTO TECNOLÓGICO E VOCACIONAL AVANÇADO', 0, 1, 'C');
        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 5, 'COORDENADORIA TÉCNICA', 0, 1, 'C');
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 6, 'Relatório de Frequência', 0, 1, 'C');
        $this->Ln(2);

        $tableWidth = $this->getPageWidth() - ($margin * 2);
        $colWidth = $tableWidth / 3;
        $rowHeightBase = 12.0;
        $cursoLinhas = $this->splitTextoCurso((string)($this->info['curso'] ?? ''), 41);
        $rowHeightTop = count($cursoLinhas) > 1 && trim((string)($cursoLinhas[1] ?? '')) !== '' ? 16.0 : $rowHeightBase;

        $drawInfoCell = function (float $x, float $y, float $width, float $rowHeight, string $label, string $value, bool $multiLine = false) use ($cursoLinhas): void {
            $this->Rect($x, $y, $width, $rowHeight);
            $this->SetXY($x + 2, $y + 2.2);
            $this->SetFont('helvetica', 'B', 7);
            $this->Cell($width - 4, 3, mb_strtoupper($label, 'UTF-8'), 0, 1, 'L');
            $this->SetFont('helvetica', '', 8.5);
            if ($multiLine) {
                $linha1 = (string)($cursoLinhas[0] ?? '');
                $linha2 = (string)($cursoLinhas[1] ?? '');
                $this->SetXY($x + 2, $y + 6.0);
                $this->Cell($width - 4, 4, $linha1, 0, 1, 'L');
                if (trim($linha2) !== '') {
                    $this->SetXY($x + 2, $y + 10.0);
                    $this->Cell($width - 4, 4, $linha2, 0, 0, 'L');
                }
                return;
            }
            $this->SetXY($x + 2, $y + 6.0);
            $this->Cell($width - 4, 4, $value, 0, 0, 'L');
        };

        $startX = $this->GetX();
        $startY = $this->GetY();
        $drawInfoCell($startX, $startY, $colWidth, $rowHeightTop, 'Curso', (string)($this->info['curso'] ?? ''), true);
        $drawInfoCell($startX + $colWidth, $startY, $colWidth, $rowHeightTop, 'Projeto', (string)($this->info['projeto'] ?? ''));
        $drawInfoCell($startX + ($colWidth * 2), $startY, $colWidth, $rowHeightTop, 'Termo', (string)($this->info['termo'] ?? ''));

        $startY += $rowHeightTop;
        $drawInfoCell($startX, $startY, $colWidth, $rowHeightBase, 'Carga horária', (string)($this->info['carga'] ?? ''));
        $drawInfoCell($startX + $colWidth, $startY, $colWidth, $rowHeightBase, 'Turma', (string)($this->info['turma'] ?? ''));
        $drawInfoCell($startX + ($colWidth * 2), $startY, $colWidth, $rowHeightBase, 'Intervalo', (string)($this->info['intervalo'] ?? ''));

        $this->SetY($startY + $rowHeightBase);

        $this->Ln(3);
        $headerRowH = 14.0;
        $headerRowStartX = $this->GetX();
        $headerRowStartY = $this->GetY();
        if (!$this->skipTableHeaderRow) {
            $this->SetFont('helvetica', 'B', 8);
            $this->Cell($this->wNum, $headerRowH, 'No', 1, 0, 'C');
            $this->Cell($this->wNome, $headerRowH, ' Nome do beneficiário(a)', 1, 0, 'L');
            foreach ($this->dias as $index => $dia) {
                $dayWidth = (float)($this->dayWidths[$index] ?? $this->wDia);
                $this->drawRotatedHeaderCell($dayWidth, $headerRowH, $dia);
            }
            $this->SetXY($headerRowStartX, $headerRowStartY + $headerRowH);
        }
        $this->bodyStartY = $this->GetY();
    }

    public function Footer(): void
    {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 5, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

$pdf = new RelatorioFrequenciaIntervaloPdf($orientation, 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('ConectaOSC');
$pdf->SetAuthor('ConectaOSC');
$pdf->SetTitle('Relatório de Frequência por Período');
$pdf->SetMargins($margin, 70, $margin);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(true, 18);
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);
$pdf->logoSistemaPath = $logoSistemaPath;
$pdf->logoProjetoPath = $logoProjetoPath;
$pdf->info = [
    'curso' => $cursoNome,
    'projeto' => $nomeProjeto,
    'turma' => $turmaNome,
    'intervalo' => $dataInicioFormatada . ' a ' . $dataFimFormatada,
    'tipo' => $cursoTipo,
    'duracao' => $cursoDuracao,
    'carga' => $cursoCH,
    'termo' => $cursoTermo,
];
$pdf->dias = $dias;
$pdf->wNum = $wNum;
$pdf->wNome = $wNome;
$pdf->wDia = $wDia;
$pdf->dayWidths = $dayWidths;

$pdf->AddPage();
$pdf->SetFont('helvetica', '', 8);
$pdf->SetY($pdf->bodyStartY);

$indice = 1;
$paginaAtual = (int)$pdf->getPage();
foreach ($alunos as $idAluno) {
    $info = $alunosInfo[$idAluno] ?? null;
    if (!$info) {
        continue;
    }

    if ($pdf->GetY() > ($pdf->getPageHeight() - 24)) {
        $pdf->AddPage();
        $paginaAtual = (int)$pdf->getPage();
        $pdf->SetY($pdf->bodyStartY);
    } elseif ((int)$pdf->getPage() !== $paginaAtual) {
        $paginaAtual = (int)$pdf->getPage();
        $pdf->SetY($pdf->bodyStartY);
    }

    $pdf->Cell($wNum, 6, (string)$indice, 1, 0, 'C');
    $pdf->Cell($wNome, 6, (string)($info['nome'] ?? ''), 1, 0, 'L');

    foreach ($dias as $indexDia => $dia) {
        $status = (string)($tabela[$idAluno][$dia] ?? 'NA');
        $dayWidth = (float)($dayWidths[$indexDia] ?? $wDia);
        $pdf->Cell($dayWidth, 6, $status, 1, 0, 'C');
    }
    $pdf->Ln();
    $indice++;
}

// Se o auto-break do TCPDF moveu o cursor para dentro do cabeçalho, reposiciona abaixo dele
if ($pdf->GetY() < $pdf->bodyStartY) {
    $pdf->SetY($pdf->bodyStartY);
}
$pdf->Ln(2);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->MultiCell(0, 5, 'Obs.: P = Presença; F = Falta; FJ = Falta Justificada; NA = Não se Aplica.', 0, 'L');

if (!$assinar) {
    $pdf->Output('relatorio_frequencia_intervalo.pdf', 'I');
    exit;
}

if ($invertextoApiToken === '') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<div class="alert alert-danger">Token da Invertexto não configurado.</div>';
    exit;
}

$idColaborador = (int)($_SESSION['Cod'] ?? 0);
$nomeCompleto = trim((string)(($_SESSION['Nome'] ?? '') . ' ' . ($_SESSION['Sobrenome'] ?? '')));
if ($nomeCompleto === '') {
    $nomeCompleto = 'Colaborador';
}

$cargoPadrao = '';
if ($idColaborador > 0) {
    $stmtCargo = $pdo->prepare('SELECT COALESCE(Cargo, "") AS Cargo FROM tbUser WHERE IdColaborador = ? LIMIT 1');
    $stmtCargo->execute([$idColaborador]);
    $cargoPadrao = safeText((string)($stmtCargo->fetchColumn() ?: ''), 120);
}

$cargoParaExibir = '';
if ($incluirCargo) {
    $cargoParaExibir = $cargoPersonalizado !== '' ? $cargoPersonalizado : $cargoPadrao;
}
$nomeCursivo = $nomeCompleto;
$nomeUpper = $nomeCompleto;
if ($cargoParaExibir !== '') {
    $nomeUpper .= ' - ' . $cargoParaExibir;
}
$nomeUpper = mb_strtoupper($nomeUpper, 'UTF-8');

$publicBaseWithScheme = assinaturaResolvePublicBaseUrl((string)$BASE_para_URL);
$assinaturaDigitalPublic = assinaturaResolveAssinaturaDigitalPublicUrl((string)$BASE_para_URL);
$timestampAssinatura = time();
$codigoBase = assinaturaGerarCodigoValidacaoCurto($pdo, $timestampAssinatura);
$linkValidacao = rtrim($publicBaseWithScheme, '/') . '/assinatura/pdf/validar/?code=' . urlencode($codigoBase);
$qrURL = 'https://api.invertexto.com/v1/qrcode?token=' . rawurlencode($invertextoApiToken) . '&text=' . urlencode($linkValidacao);
$qrData = @file_get_contents($qrURL);
if (!$qrData) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<div class="alert alert-danger">Erro ao gerar QR Code.</div>';
    exit;
}

$pathStorageBase = rtrim($BASE_para_PATH, '/\\') . '/app/storage/assinatura/';
$pathFinais = $pathStorageBase . 'assinados/';
$pathQR = $pathStorageBase . 'qrcodes/';
$pathTemp = $pathStorageBase . 'tmp/';
if (!is_dir($pathFinais)) {
    @mkdir($pathFinais, 0775, true);
}
if (!is_dir($pathQR)) {
    @mkdir($pathQR, 0775, true);
}
if (!is_dir($pathTemp)) {
    @mkdir($pathTemp, 0775, true);
}

$qrFile = $pathQR . $codigoBase . '.png';
file_put_contents($qrFile, $qrData);

$fontFile = $BASE_para_PATH . '/api/lib/tcpdf/fonts/Licorice-Regular.ttf';
$fontName = 'helvetica';
if (is_file($fontFile)) {
    $fontName = TCPDF_FONTS::addTTFfont($fontFile, 'TrueTypeUnicode', '', 32) ?: 'helvetica';
}

$lastPage = $pdf->getNumPages();
$pdf->setPage($lastPage);
$pageWidthSigned = $pdf->getPageWidth();
$pageHeightSigned = $pdf->getPageHeight();
$neededHeight = 22.0;
$startY = max($pdf->GetY(), $pdf->bodyStartY) + 4.0;
$signatureNeedsNewPage = (($startY + $neededHeight) > ($pageHeightSigned - 20.0));

$nomeArquivoFinal = assinaturaGerarNomeArquivoFinal($pathFinais, max(1, $idColaborador));
$pathFinalPdf = $pathFinais . $nomeArquivoFinal;
$tempOriginalPdf = $pathTemp . 'frequencia_intervalo_base_' . uniqid('', true) . '.pdf';
$pdf->Output($tempOriginalPdf, 'F');

$signatureLayout = [
    'qrSize' => 18.0,
    'gap' => 3.0,
    'textWidth' => 102.0,
    'neededHeight' => 22.0,
    'scriptFont' => 13.0,
    'labelFont' => 7.0,
    'upperFont' => 10.0,
    'codeFont' => 7.0,
    'authFont' => 6.0,
    'nameOffsetY' => 1.7,
    'labelOffsetY' => 4.8,
    'upperOffsetY' => 8.9,
    'codeOffsetY' => -1.65,
    'authOffsetY' => 2.5,
];
$groupX = 0.0;
$groupY = 0.0;

try {
    $signedPdf = new Fpdi();
    $signedPdf->setPrintHeader(false);
    $signedPdf->setPrintFooter(false);
    $signedPdf->SetAutoPageBreak(false, 0);
    $pageCount = $signedPdf->setSourceFile($tempOriginalPdf);
    if ($pageCount === 1 && $signatureNeedsNewPage) {
        $compactLayouts = [
            [
                'qrSize' => 12.0,
                'gap' => 2.0,
                'textWidth' => 78.0,
                'neededHeight' => 14.0,
                'scriptFont' => 10.0,
                'labelFont' => 6.0,
                'upperFont' => 8.0,
                'codeFont' => 6.0,
                'authFont' => 5.5,
                'nameOffsetY' => 1.2,
                'labelOffsetY' => 3.3,
                'upperOffsetY' => 5.9,
                'codeOffsetY' => -1.0,
                'authOffsetY' => 1.4,
            ],
            [
                'qrSize' => 10.0,
                'gap' => 2.0,
                'textWidth' => 68.0,
                'neededHeight' => 11.5,
                'scriptFont' => 8.5,
                'labelFont' => 5.5,
                'upperFont' => 7.0,
                'codeFont' => 5.5,
                'authFont' => 5.0,
                'nameOffsetY' => 0.8,
                'labelOffsetY' => 2.7,
                'upperOffsetY' => 4.9,
                'codeOffsetY' => -0.6,
                'authOffsetY' => 0.8,
            ],
        ];

        foreach ($compactLayouts as $compactLayout) {
            if (($startY + (float)$compactLayout['neededHeight']) <= ($pageHeightSigned - 20.0)) {
                $signatureLayout = $compactLayout;
                $signatureNeedsNewPage = false;
                break;
            }
        }
    }

    $footerPrefix = 'Documento assinado digitalmente, codigo ' . $codigoBase . '. Confira autenticidade em ';
    $footerUrl = $assinaturaDigitalPublic;

    for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
        $tplIdx = $signedPdf->importPage($pageNumber);
        $size = $signedPdf->getTemplateSize($tplIdx);

        $signedPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $signedPdf->useTemplate($tplIdx, 0, 0, $size['width'], $size['height'], true);

        $signedPdf->SetFont('helvetica', '', 6);
        $signedPdf->SetTextColor(80, 80, 80);
        $prefixLen = $signedPdf->GetStringWidth($footerPrefix);
        $urlLen = $signedPdf->GetStringWidth($footerUrl);
        $textLen = $prefixLen + $urlLen;
        $xBase = 5.0;
        $yCenter = $size['height'] / 2;
        $yBase = $yCenter + ($textLen / 2);

        $signedPdf->StartTransform();
        $signedPdf->Rotate(90, $xBase, $yBase);
        $signedPdf->Text($xBase, $yBase, $footerPrefix);
        $signedPdf->SetFont('helvetica', 'U', 6);
        $signedPdf->SetTextColor(0, 102, 204);
        $urlX = $xBase + $prefixLen;
        $signedPdf->Text($urlX, $yBase, $footerUrl);
        $signedPdf->Link($urlX, $yBase - 2.1, $urlLen + 0.6, 3.0, $footerUrl);
        $signedPdf->StopTransform();

        if ($pageNumber !== $pageCount) {
            continue;
        }

        if ($signatureNeedsNewPage) {
            $signedPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        }

        $currentWidth = $signedPdf->getPageWidth();
        $currentHeight = $signedPdf->getPageHeight();
        $qrSize = (float)$signatureLayout['qrSize'];
        $gap = (float)$signatureLayout['gap'];
        $textWidth = (float)$signatureLayout['textWidth'];
        $neededHeightCurrent = (float)$signatureLayout['neededHeight'];
        $groupWidth = $qrSize + $gap + $textWidth;
        $groupX = max(8.0, ($currentWidth - $groupWidth) / 2);
        $groupY = $signatureNeedsNewPage ? 70.0 : $startY;

        if (($groupY + $neededHeightCurrent) > ($currentHeight - 20.0)) {
            $groupY = max(20.0, $currentHeight - 20.0 - $neededHeightCurrent);
        }

        $signedPdf->Image($qrFile, $groupX, $groupY, $qrSize, $qrSize);

        $textX = $groupX + $qrSize + $gap - 4.0;
        $textY = $groupY + (float)$signatureLayout['nameOffsetY'];

        $signedPdf->SetFont($fontName, '', (float)$signatureLayout['scriptFont']);
        $signedPdf->SetTextColor(0, 0, 0);
        $signedPdf->SetXY($textX, $textY);
        $signedPdf->Cell($textWidth, 6, $nomeCursivo, 0, 0, 'L');
        $larguraCursiva = $signedPdf->GetStringWidth($nomeCursivo);

        $signedPdf->SetFont('helvetica', '', (float)$signatureLayout['labelFont']);
        $signedPdf->SetXY($textX, $textY + (float)$signatureLayout['labelOffsetY']);
        $signedPdf->Cell($textWidth, 5, 'Assinatura digital de:', 0, 0, 'L');

        $upperSize = (float)$signatureLayout['upperFont'];
        $signedPdf->SetFont('helvetica', 'B', $upperSize);
        while ($signedPdf->GetStringWidth($nomeUpper) > $larguraCursiva && $upperSize > 6.0) {
            $upperSize -= 0.5;
            $signedPdf->SetFont('helvetica', 'B', $upperSize);
        }
        $signedPdf->SetXY($textX, $textY + (float)$signatureLayout['upperOffsetY']);
        $signedPdf->Cell($textWidth, 6, $nomeUpper, 0, 0, 'L');

        $signedPdf->SetFont('helvetica', '', (float)$signatureLayout['codeFont']);
        $signedPdf->SetTextColor(50, 50, 50);
        $signedPdf->SetXY($groupX, $groupY + $qrSize + (float)$signatureLayout['codeOffsetY']);
        $signedPdf->Cell($groupWidth, 4, 'Codigo para validacao: ' . $codigoBase, 0, 0, 'L');

        $signedPdf->SetFont('helvetica', '', (float)$signatureLayout['authFont']);
        $signedPdf->SetTextColor(80, 80, 80);
        $authY = $groupY + $qrSize + (float)$signatureLayout['authOffsetY'];
        $signedPdf->SetXY($groupX, $authY);
        $signedPdf->Cell($groupWidth, 3.6, 'Confira autenticidade em: ' . $assinaturaDigitalPublic, 0, 0, 'L');
        $signedPdf->Link($groupX, $authY, $groupWidth, 3.6, $assinaturaDigitalPublic);
    }

    $signedPdf->Output($pathFinalPdf, 'F');
} finally {
    if (is_file($tempOriginalPdf)) {
        @unlink($tempOriginalPdf);
    }
    if (is_file($qrFile)) {
        @unlink($qrFile);
    }
}
$nomeDocumento = safeText('Relatório de frequência - ' . $cursoNome . ' (' . $dataInicioFormatada . ' a ' . $dataFimFormatada . ')', 180);
$stmtIns = $pdo->prepare("
    INSERT INTO tbpdf_assinado
        (IdColaborador, NomeOriginal, NomeDocumento, NomeArquivo, AssinaturaBase64, TimestampAssinatura, Xpos, Ypos, urlValidacao)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmtIns->execute([
    $idColaborador,
    'frequencia_intervalo:' . date('YmdHis'),
    $nomeDocumento,
    $nomeArquivoFinal,
    $codigoBase,
    $timestampAssinatura,
    (int)round($groupX),
    (int)round($groupY),
    $linkValidacao,
]);

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $nomeArquivoFinal . '"');
readfile($pathFinalPdf);
exit;
