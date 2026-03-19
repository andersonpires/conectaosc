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

require_once $_SESSION['BASE_PATH'] . '/checa-token.php';

// =====================================
//  PASTAS
// =====================================
$pathBase      = $_SESSION['BASE_PATH'] . "/app/assinatura/";
$pathOriginais = $pathBase . "originais/";

$msg = "";

if (isset($_POST['enviar'])) {

    $file = $_FILES['pdf'];

    $nomeDocumento = trim($_POST['nomeDocumento']);

    if ($file['error'] === 0) {

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== "pdf") {
            $msg = "<div class='alert alert-danger'>Envie apenas PDF!</div>";
        } else {

            $nomeTemp = "pdf_" . time() . "_" . $_SESSION['Cod'] . ".pdf";
            $pathPDF  = $pathOriginais . $nomeTemp;

            if (move_uploaded_file($file['tmp_name'], $pathPDF)) {

                echo "
                        <form id='goPreview' method='POST' action='" . $_SESSION['BASE_URL'] . "/app/assinatura/PDFpreview.php'>
                            <input type='hidden' name='file' value='{$nomeTemp}'>
                            <input type='hidden' name='nomeDocumento' value='{$nomeDocumento}'>
                        </form>

                        <script>
                            document.getElementById('goPreview').submit();
                        </script>
                        ";
                exit;
            }
        }
    }
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
                <div class="container-fluid p-0">

                    <div class="container mt-4">

                        <h2>Enviar Documento PDF</h2>
                        <p>Selecione o PDF para iniciar o processo de assinatura digital.</p>

                        <?= $msg ?>

                        <form method="POST" enctype="multipart/form-data">

                            <div class="form-group row mb-3">
                                <label>Arquivo PDF:</label>
                                <input type="file" name="pdf" class="form-control" accept="application/pdf" required>
                            </div>

                            <div class="form-group row mb-3">
                                <label>Nome do Documento:</label>
                                <input type="text" name="nomeDocumento" class="form-control" placeholder="Ex: Ofício XYZ" required>
                            </div>


                            <button type="submit" name="enviar" class="btn btn-success">
                                Próximo
                            </button>
                        </form>

                    </div>

                </div>
            </main>

            <footer class="footer">
                <?php require_once $_SESSION['BASE_PATH'] . '/template/footer.php'; ?>
            </footer>

        </div>
    </div>
</body>

</html>