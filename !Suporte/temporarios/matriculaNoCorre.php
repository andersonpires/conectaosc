<?php
if (ob_get_level() === 0) {
    ob_start();
}

$runtime = require __DIR__ . '/../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_path'];
$BASE_para_URL = $runtime['base_url'];
$ASSETS_IMG_PATH = (string)($runtime['assets_img_path'] ? '');
$ASSETS_IMG_URL = (string)($runtime['assets_img_url'] ? '');

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI'] ? '');
    header('Location: ' . rtrim((string)($BASE_para_URL ? ''), '/') . '/login/?redirect=' . $redirect_url);
    exit();
}

require_once $BASE_para_PATH . '/api/conectabd/conexao.php';
require_once $BASE_para_PATH . '/api/lib/tcpdf/tcpdf.php';

function suporteResolvePublicUrl(string $baseUrl, string $relativePath): string
{
    $host = (string)($_SERVER['HTTP_HOST'] ? '');
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base = rtrim((string)$baseUrl, '/');
    if ($host !== '' && !preg_match('#^https?://#i', $base)) {
        $base = $scheme . '://' . $host . $base;
    }
    return rtrim($base, '/') . '/' . ltrim($relativePath, '/');
}

function suporteResolveFilePath(string $rawPath, string $basePath, string $assetsImgPath = '', string $assetsImgUrl = '', string $baseUrl = ''): string
{
    $rawPath = trim(str_replace('\\', '/', $rawPath));
    if ($rawPath === '') {
        return '';
    }

    if (preg_match('/^https?:\/\//i', $rawPath)) {
        return $rawPath;
    }

    $basePath = rtrim($basePath, '/\\');
    $rawPath = ltrim($rawPath, '/');

    $candidates = [
        $basePath . '/' . $rawPath,
        dirname($basePath) . '/' . $rawPath,
        $basePath . '/app/assets/img/' . $rawPath,
        $basePath . '/app/assets/img/logos/' . $rawPath,
        $basePath . '/app/assets/img/fotos/' . $rawPath,
    ];

    if ($assetsImgPath !== '') {
        $assetsImgPath = rtrim($assetsImgPath, '/\\');
        $candidates[] = $assetsImgPath . '/' . $rawPath;
        $candidates[] = $assetsImgPath . '/logos/' . basename($rawPath);
        $candidates[] = $assetsImgPath . '/fotos/' . basename($rawPath);
    }

    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    if ($assetsImgUrl !== '') {
        $imgBase = rtrim($assetsImgUrl, '/');
        if (preg_match('#^https?://#i', $imgBase)) {
            return $imgBase . '/' . ltrim($rawPath, '/');
        }
        return suporteResolvePublicUrl($baseUrl, $imgBase . '/' . ltrim($rawPath, '/'));
    }

    return $baseUrl !== '' ? suporteResolvePublicUrl($baseUrl, 'assets/img/' . ltrim($rawPath, '/')) : '';
}

function suporteResolveFotoPath(string $foto, string $basePath, string $assetsImgPath = '', string $assetsImgUrl = '', string $baseUrl = ''): string
{
    $foto = trim($foto);
    if ($foto === '') {
        $foto = 'padrao.jpg';
    }

    $path = suporteResolveFilePath('fotos/' . $foto, $basePath, $assetsImgPath, $assetsImgUrl, $baseUrl);
    if ($path !== '') {
        return $path;
    }

    return suporteResolveFilePath('fotos/padrao.jpg', $basePath, $assetsImgPath, $assetsImgUrl, $baseUrl);
}

function suporteNormalizaNomeAluno(string $nome): string
{
    $nome = trim($nome);
    if ($nome === '') {
        return '';
    }

    $nome = preg_replace('/^\s*\([^)]*\)\s*/u', '', $nome);
    return trim((string)$nome);
}

class MatriculaNoCorrePDF extends TCPDF
{
    public string $logoEsquerda = '';
    public string $logoDireita = '';
    public float $logoHeightMm = 20;
    public float $headerTopMm = 8;
    public float $headerLineGapMm = 2;

    public function Header(): void
    {
        $logoTop = $this->headerTopMm;

        if ($this->logoEsquerda !== '' && (is_file($this->logoEsquerda) || preg_match('/^https?:\/\//i', $this->logoEsquerda))) {
            $this->Image($this->logoEsquerda, 15, $logoTop, 0, $this->logoHeightMm, '', '', 'T', false, 300);
        }

        if ($this->logoDireita !== '' && (is_file($this->logoDireita) || preg_match('/^https?:\/\//i', $this->logoDireita))) {
            $rightX = $this->getPageWidth() - 15 - 35;
            $this->Image($this->logoDireita, $rightX, $logoTop, 0, $this->logoHeightMm, '', '', 'T', false, 300);
        }

        $lineY = $logoTop + $this->logoHeightMm + $this->headerLineGapMm;
        $this->Line(15, $lineY, $this->getPageWidth() - 15, $lineY);
    }

    public function Footer(): void
    {
        $pageHeight = $this->getPageHeight();
        $lineY = $pageHeight - 18;
        $this->Line(15, $lineY, $this->getPageWidth() - 15, $lineY);
        $this->SetY($lineY + 2);
        $this->SetFont('helvetica', '', 8);
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

$idCurso = 8;
$idProjetoLogo = 8;
$debug = isset($_GET['debug']) && $_GET['debug'] === '1';

$config = $pdo->query("SELECT LogoImpressao FROM tbConfig LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
$logoImpressao = (string)($config['LogoImpressao'] ? '');
if ($logoImpressao === '') {
    $logoImpressao = 'logos/LogoImpressao.jpg';
}

$projeto = $pdo->prepare("SELECT LogoProjeto FROM tbProjeto WHERE IdProjeto = ? LIMIT 1");
$projeto->execute([$idProjetoLogo]);
$projetoRow = $projeto->fetch(PDO::FETCH_ASSOC) ?: [];
$logoProjeto = (string)($projetoRow['LogoProjeto'] ? '');

$logoEsquerdaPath = suporteResolveFilePath($logoImpressao, $BASE_para_PATH, $ASSETS_IMG_PATH, $ASSETS_IMG_URL, $BASE_para_URL);
$logoDireitaPath = suporteResolveFilePath($logoProjeto, $BASE_para_PATH, $ASSETS_IMG_PATH, $ASSETS_IMG_URL, $BASE_para_URL);

$sqlTexto = "
    SELECT
        m.IdMatricula,
        a.IdUsuario,
        a.Nome,
        a.SexoBio,
        a.Nascimento,
        a.Endereco,
        a.Bairro,
        a.Cidade,
        a.Foto,
        c.NomeCurso,
        MAX(ch.IdChamada) AS IdChamadaRef
    FROM tbAluno a
    INNER JOIN tbMatricula m
        ON m.IdUsuario = a.IdUsuario
       AND m.IdCurso = :idCurso
    INNER JOIN tbCurso c
        ON c.IdCurso = m.IdCurso
    LEFT JOIN tbChamada ch
        ON ch.IdMatricula = m.IdMatricula
       AND ch.IdCurso = m.IdCurso
    GROUP BY
        m.IdMatricula,
        a.IdUsuario,
        a.Nome,
        a.SexoBio,
        a.Nascimento,
        a.Endereco,
        a.Bairro,
        a.Cidade,
        a.Foto,
        c.NomeCurso
    ORDER BY a.Nome ASC
";

$sql = $pdo->prepare($sqlTexto);
$sql->execute([':idCurso' => $idCurso]);
$registros = $sql->fetchAll(PDO::FETCH_ASSOC);
$registros = array_values(array_filter(array_map(static function (array $row): array {
    $row['Nome'] = suporteNormalizaNomeAluno((string)($row['Nome'] ? ''));
    return $row;
}, $registros), static function (array $row): bool {
    return trim((string)($row['Nome'] ? '')) !== '';
}));

if (!$registros) {
    if ($debug) {
        $stmtA = $pdo->prepare("SELECT COUNT(*) FROM tbMatricula WHERE IdCurso = ?");
        $stmtA->execute([$idCurso]);
        $qtdMatriculas = (int)$stmtA->fetchColumn();

        $stmtB = $pdo->prepare("SELECT COUNT(*) FROM tbMatricula m JOIN tbAluno a ON a.IdUsuario = m.IdUsuario WHERE m.IdCurso = ?");
        $stmtB->execute([$idCurso]);
        $qtdComAluno = (int)$stmtB->fetchColumn();

        header('Content-Type: text/plain; charset=utf-8');
        echo "DEBUG matriculaNoCorre\n";
        echo "IdCurso: {$idCurso}\n";
        echo "Matrículas no curso: {$qtdMatriculas}\n";
        echo "Matrículas com aluno associado: {$qtdComAluno}\n";
        echo "\nSQL executada:\n{$sqlTexto}\n";
        exit;
    }

    die("<div class='alert alert-warning'>Nenhum registro encontrado para IdCurso = 8.</div>");
}

$nomeCurso = trim((string)($registros[0]['NomeCurso'] ? ''));
$titulo = 'Relação de Inscritos';
if ($nomeCurso !== '') {
    $titulo .= ' (' . $nomeCurso . ')';
}

$pdf = new MatriculaNoCorrePDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->logoEsquerda = $logoEsquerdaPath;
$pdf->logoDireita = $logoDireitaPath;
$pdf->SetCreator('Conecta OSC');
$pdf->SetAuthor('Conecta OSC');
$pdf->SetTitle($titulo);
$pdf->SetMargins(10, 40, 10);
$pdf->SetHeaderMargin(0);
$pdf->SetAutoPageBreak(true, 18);
$pdf->SetFont('helvetica', '', 9);

$linhasPorPagina = 7;
$contador = 1;
$paginas = array_chunk($registros, $linhasPorPagina);

foreach ($paginas as $bloco) {
    $pdf->AddPage();

    $html = '<h3 style="text-align:center;">' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</h3>';
    $html .= '<table border="1" cellpadding="3" cellspacing="0" width="100%">';
    $html .= '
        <tr style="background-color:#f3f4f6;font-weight:bold;">
            <th width="4%" align="center">Nº</th>
            <th width="8%" align="center">Foto</th>
            <th width="24%" align="center">Nome</th>
            <th width="8%" align="center">SexoBio</th>
            <th width="10%" align="center">Nascimento</th>
            <th width="24%" align="center">Endereço</th>
            <th width="11%" align="center">Bairro</th>
            <th width="11%" align="center">Cidade</th>
        </tr>
    ';

    foreach ($bloco as $row) {
        $fotoPath = suporteResolveFotoPath((string)($row['Foto'] ? ''), $BASE_para_PATH, $ASSETS_IMG_PATH, $ASSETS_IMG_URL, $BASE_para_URL);
        $fotoTag = $fotoPath !== ''
            ? '<img src="' . htmlspecialchars($fotoPath, ENT_QUOTES, 'UTF-8') . '" height="40" />'
            : '-';

        $html .= '
            <tr>
                <td width="4%" align="center">' . $contador . '</td>
                <td width="8%" align="center">' . $fotoTag . '</td>
                <td width="24%">' . htmlspecialchars((string)($row['Nome'] ? ''), ENT_QUOTES, 'UTF-8') . '</td>
                <td width="8%" align="center">' . htmlspecialchars((string)($row['SexoBio'] ? ''), ENT_QUOTES, 'UTF-8') . '</td>
                <td width="10%" align="center">' . htmlspecialchars((string)($row['Nascimento'] ? ''), ENT_QUOTES, 'UTF-8') . '</td>
                <td width="24%">' . htmlspecialchars((string)($row['Endereco'] ? ''), ENT_QUOTES, 'UTF-8') . '</td>
                <td width="11%">' . htmlspecialchars((string)($row['Bairro'] ? ''), ENT_QUOTES, 'UTF-8') . '</td>
                <td width="11%">' . htmlspecialchars((string)($row['Cidade'] ? ''), ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
        ';
        $contador++;
    }

    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, '');
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

$pdf->Output('matriculaNoCorre_curso8.pdf', 'I');
exit;
