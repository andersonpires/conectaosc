<?php
session_set_cookie_params(['httponly' => true]);
ini_set('session.gc_maxlifetime', 86400);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

// =====================================
//  CHECK DE LOGIN + TOKEN
// =====================================
if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: " . $_SESSION['BASE_URL'] . "/login.php?redirect={$redirect_url}");
    exit;
}

require_once $_SESSION['BASE_PATH'] . '/lib/tcpdf/tcpdf.php';
require_once $_SESSION['BASE_PATH'] . '/lib/tcpdf/fpdi/autoload.php';
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

use setasign\Fpdi\Tcpdf\Fpdi;

// =====================================
//  DADOS DO USUÁRIO
// =====================================
$idColab = $_SESSION['Cod'];
$nomeCompleto = $_SESSION['Nome'] . " " . $_SESSION['Sobrenome'];
$nomeUpper = mb_strtoupper($nomeCompleto, 'UTF-8');

// =====================================
//  ARQUIVOS
// =====================================
$pathBase      = $_SESSION['BASE_PATH'] . "/app/assinatura/";
$pathOriginais = $pathBase . "originais/";
$pathFinais    = $pathBase . "assinados/";
$pathQR        = $pathBase . "qrcodes/";

$nomeArquivo = $_POST['file'] ?? '';
$nomeDocumento = $_POST['nomeDocumento'] ?? '';
$pathOriginalPDF = $pathOriginais . $nomeArquivo;

// validações básicas
if ($nomeArquivo === '' || $nomeDocumento === '') {
    die("<div class='alert alert-danger'>Erro: Dados não recebidos.</div>");
}

if (!file_exists($pathOriginalPDF)) {
    die("<div class='alert alert-danger'>Erro: Arquivo original não encontrado.</div>");
}


// =====================================
//  RECEBE POSIÇÃO ENVIADA DO PREVIEW
// =====================================
$xPx = floatval($_POST['xpos']);
$yPx = floatval($_POST['ypos']);

$canvasW = floatval($_POST['canvasWidth']);
$canvasH = floatval($_POST['canvasHeight']);

if ($canvasW <= 0 || $canvasH <= 0) {
    die("Erro: Dimensões inválidas.");
}

// =====================================
//  CRIA QR CODE
// =====================================
$tokenQR = "23083|xIkXyKYtUHwxOrhuZK6ej7ZuxkHbAOPK";

$codigoBase = base64_encode(time() . "_" . $idColab);
$linkValidacao = $_SESSION['BASE_URL'] . "/app/assinatura/verPDF.php?code=" . $codigoBase;
$linkQrCode = "https://iteva.com.br/conectaosc/app/assinatura/verPDF.php?code=" . $codigoBase;


// $linkArquivoFinal = $_SESSION['BASE_URL'] . "/conectaosc/app/assinatura/assinados/verPDF.php?=" . $nomeArquivo;
$urlPDF  = "https://iteva.com.br/conectaosc/app/assinatura/assinados/" . $nomeArquivo;
$urlPDFInt  = "/conectaosc/app/assinatura/assinados/" . $nomeArquivo;
$qrURL = "https://api.invertexto.com/v1/qrcode?token={$tokenQR}&text=" . urlencode($linkQrCode);
$qrData = file_get_contents($qrURL);

if (!$qrData) {
    die("<div class='alert alert-danger'>Erro ao gerar QR Code!</div>");
}

$qrFile = $pathQR . $codigoBase . ".png";
file_put_contents($qrFile, $qrData);

// =====================================
//  PROCESSAMENTO PDF
// =====================================
try {
    $pdf = new Fpdi();
    $pageCount = $pdf->setSourceFile($pathOriginalPDF);

    $fontFile = $_SESSION['BASE_PATH'] . "/lib/tcpdf/fonts/Licorice-Regular.ttf";
    $fontName = TCPDF_FONTS::addTTFfont($fontFile, 'TrueTypeUnicode', '', 32);

    $pdf->SetAutoPageBreak(false, 0);

    for ($i = 1; $i <= $pageCount; $i++) {

        $tplIdx = $pdf->importPage($i);
        $size   = $pdf->getTemplateSize($tplIdx);

        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($tplIdx, 0, 0, $size['width'], $size['height'], true);

        // ============================
        // RODAPÉ (todas menos a última)
        // ============================
        if ($i < $pageCount) {
            $pdf->SetFont('helvetica', '', 6);
            $pdf->SetTextColor(80, 80, 80);
            $pdf->SetXY(10, $size['height'] - 10);

            $pdf->Cell(
                $size['width'] - 20,
                5,
                "Documento assinado digitalmente, código " . $codigoBase . ". Confira autenticidade em https://iteva.com.br/conectaosc/assinaturadigital",
                0,
                0,
                'C'
            );
        }

        // =============================================
        // ÚLTIMA PÁGINA — POSICIONA ASSINATURA + QR + TEXTO
        // =============================================
        if ($i == $pageCount) {

            // CONVERSÃO: pixel -> milímetro
            $realX = ($xPx / $canvasW) * $size['width'];
            $realY = ($yPx / $canvasH) * $size['height'];

            // Margens de segurança
            if ($realX < 5) $realX = 5;
            if ($realY < 5) $realY = 5;

            if ($realX > $size['width'] - 60) $realX = $size['width'] - 60;
            if ($realY > $size['height'] - 30) $realY = $size['height'] - 30;

            // -----------------------
            // QR CODE
            // -----------------------
            $qrX = $realX;
            $qrY = $realY;

            if ($qrX < 0) $qrX = 0;

            $pdf->Image($qrFile, $qrX, $qrY, 18, 18);

            // -----------------------
            // TEXTO DO CÓDIGO ABAIXO DO QR
            // -----------------------
            $pdf->SetFont('helvetica', '', 7);
            $pdf->SetTextColor(50, 50, 50);

            // Alinha 19mm abaixo do QR
            $pdf->SetXY($qrX, $qrY + 17);

            $pdf->Cell(
                0,                             // largura automática (até fim da página)
                4,                             // altura da linha
                "Código para validação: " . $codigoBase,
                0,
                0,
                'L'
            );


            // -----------------------
            // ASSINATURA AO LADO DO QR
            // -----------------------
            $pdf->SetFont($fontName, '', 18);
            $pdf->SetTextColor(0, 0, 0);

            $assinaturaX = $realX + 18; // ajustado para ficar mais perto
            $assinaturaY = $realY;

            $pdf->SetXY($assinaturaX, $assinaturaY);
            $pdf->Cell(0, 6, $nomeCompleto);

            // Texto: "Assinatura digital de:"
            $pdf->SetFont('helvetica', '', 7);
            $pdf->SetXY($assinaturaX, $assinaturaY + 6);
            $pdf->Cell(0, 5, "Assinatura digital de:");

            // Nome em caixa alta e negrito
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetXY($assinaturaX, $assinaturaY + 11);
            $pdf->Cell(0, 5, $nomeUpper);
        }
    }

    // Salva arquivo assinado
    $pathFinalPDF = $pathFinais . $nomeArquivo;
    $pdf->Output($pathFinalPDF, 'F');

    // APAGA O PDF ORIGINAL APÓS A ASSINATURA
    if (file_exists($pathOriginalPDF)) {
        unlink($pathOriginalPDF);
    }


    // SALVA NO BANCO
    $sql = $pdo->prepare("
        INSERT INTO tbpdf_assinado
            (IdColaborador, NomeDocumento, NomeArquivo, AssinaturaBase64, TimestampAssinatura, Xpos, Ypos, urlValidacao)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $sql->execute([
        $idColab,
        $nomeDocumento,    // nome amigável digitado pelo usuário
        $nomeArquivo,      // nome final salvo
        $codigoBase,
        time(),
        $realX,
        $realY,
        $linkValidacao
    ]);
} catch (Exception $e) {

    // Mensagem amigável para PDFs não compatíveis com FPDI
    $erro = $e->getMessage();

    // Guarda mensagem para exibir no HTML abaixo
    $msgErro = "
        <div class='alert alert-danger'>
            <h4><b>Não foi possível processar este PDF</b></h4>
            <p>Este documento usa um tipo de compressão que não é compatível com o sistema atual.</p>
            <p><b>Detalhes técnicos:</b> {$erro}</p>

            <br>
            <a href='" . $_SESSION['BASE_URL'] . "/app/assinatura/PDFupload.php' class='btn btn-secondary'>
                Tentar outro PDF
            </a>
        </div>
    ";
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $_SESSION['BASE_PATH'] . '/template/header.php'; ?>
</head>

<body>

    <div class="wrapper">

        <?php require_once $_SESSION['BASE_PATH'] . '/template/menu.php'; ?>

        <div class="main">

            <?php require_once $_SESSION['BASE_PATH'] . '/template/topo.php'; ?>
            <script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/app.js"></script>

            <main class="content">
                <div class="container mt-4">

                    <?php if (isset($msgErro)) { ?>

                        <?= $msgErro ?>

                    <?php } else { ?>

                        <div class="alert alert-success">

                            PDF assinado com sucesso!<br><br>
                            <a href="<?= $linkValidacao ?>" target="_blank" class="btn btn-primary">
                                Verificar Assinatura
                            </a>
                            &nbsp;
                            <a href="<?= $urlPDFInt ?>" target="_blank" class="btn btn-secondary">
                                Abrir PDF Assinado
                            </a>
                        </div> <!-- fim alert success -->
                    <?php } ?>
                </div>
            </main>

            <footer class="footer">
                <?php require_once $_SESSION['BASE_PATH'] . '/template/footer.php'; ?>
            </footer>

        </div>
    </div>

</body>

</html>
