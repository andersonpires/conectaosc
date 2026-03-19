<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
$appJsVersion = @filemtime($BASE_para_PATH . '/app/assets/js/app.js') ?: time();
require_once $BASE_para_PATH . '/api/legacy/checa-token.php';

// Agora trabalhamos SOMENTE via POST
$nomeArquivo = $_POST['file'] ?? '';
$nomeDocumento = $_POST['nomeDocumento'] ?? '';

if ($nomeArquivo === '' || $nomeDocumento === '') {
    die("Dados nao recebidos. Volte ao envio do PDF.");
}

// Caminho publico do PDF temporario
$pdfURL = $BASE_para_URL . "/app/storage/assinatura/originais/" . $nomeArquivo;


?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>

    <style>
        #viewer {
            border: 1px solid #ccc;
            padding: 10px;
        }

        .page-canvas {
            position: relative;
            margin-bottom: 20px;
        }

        #assinatura {
            width: 220px;
            /* antes 160px */
            height: 80px;
            /* antes 55px  */
            background: rgba(0, 0, 0, 0.12);
            border: 2px dashed #000;
            position: absolute;
            top: 30px;
            left: 30px;
            cursor: grab;
            text-align: center;
            padding-top: 30px;
            /* centraliza a palavra "Assinatura" */
            font-weight: bold;
            font-size: 14px;
            display: none;
        }
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

    <script>
        let pdfDoc = null;
        let lastPageCanvas = null;
        let dragging = false;
        let offsetX = 0,
            offsetY = 0;

        document.addEventListener("DOMContentLoaded", async () => {

            const pdfURL = "<?= $pdfURL ?>";

            pdfjsLib.GlobalWorkerOptions.workerSrc =
                "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js";

            pdfDoc = await pdfjsLib.getDocument(pdfURL).promise;

            const viewer = document.getElementById("viewer");

            viewer.appendChild(document.getElementById("assinatura"));

            for (let i = 1; i <= pdfDoc.numPages; i++) {
                const page = await pdfDoc.getPage(i);
                let viewport = page.getViewport({
                    scale: 1.3
                });

                let canvas = document.createElement("canvas");
                let ctx = canvas.getContext("2d");
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                let pageWrapper = document.createElement("div");
                pageWrapper.className = "page-canvas";
                pageWrapper.appendChild(canvas);
                viewer.appendChild(pageWrapper);

                await page.render({
                    canvasContext: ctx,
                    viewport: viewport
                }).promise;

                if (i === pdfDoc.numPages) {
                    lastPageCanvas = pageWrapper;

                    document.getElementById("canvasWidth").value = canvas.width;
                    document.getElementById("canvasHeight").value = canvas.height;

                    let box = document.getElementById("assinatura");
                    box.style.display = "block";
                    pageWrapper.appendChild(box);
                }
            }

            const box = document.getElementById("assinatura");
            const xposInput = document.getElementById("xpos");
            const yposInput = document.getElementById("ypos");

            function setBoxPosition(x, y) {
                if (!lastPageCanvas) return;
                const maxX = Math.max(0, lastPageCanvas.clientWidth - box.offsetWidth);
                const maxY = Math.max(0, lastPageCanvas.clientHeight - box.offsetHeight);
                const clampedX = Math.min(Math.max(0, x), maxX);
                const clampedY = Math.min(Math.max(0, y), maxY);

                box.style.left = clampedX + "px";
                box.style.top = clampedY + "px";
                xposInput.value = clampedX;
                yposInput.value = clampedY;
            }

            box.addEventListener("mousedown", e => {
                dragging = true;
                offsetX = e.offsetX;
                offsetY = e.offsetY;
            });

            document.addEventListener("mouseup", () => dragging = false);

            document.addEventListener("mousemove", e => {
                if (!dragging) return;

                const rect = lastPageCanvas.getBoundingClientRect();

                let x = e.clientX - rect.left - offsetX;
                let y = e.clientY - rect.top - offsetY;

                setBoxPosition(x, y);
            });

            lastPageCanvas.addEventListener("click", e => {
                if (e.target === box || box.contains(e.target)) return;
                const rect = lastPageCanvas.getBoundingClientRect();
                const clickX = e.clientX - rect.left;
                const clickY = e.clientY - rect.top;
                setBoxPosition(clickX, clickY);
            });

            // Garante coordenadas válidas mesmo sem interação manual.
            setBoxPosition(parseFloat(box.style.left) || 30, parseFloat(box.style.top) || 30);
        });
    </script>

</head>

<body>

    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>

        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>
            <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js?v=<?php echo $appJsVersion; ?>"></script>

            <main class="content">
                <div class="container mt-4">

                    <h2>Posicione a assinatura digital</h2>

                    <div id="viewer"></div>

                    <form method="POST" action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/assinatura/pdf/finalizar/">

                        <input type="hidden" name="file" value="<?= $nomeArquivo ?>">
                        <input type="hidden" name="nomeDocumento" value="<?= htmlspecialchars($nomeDocumento) ?>">

                        <input type="hidden" name="xpos" id="xpos">
                        <input type="hidden" name="ypos" id="ypos">

                        <input type="hidden" name="canvasWidth" id="canvasWidth">
                        <input type="hidden" name="canvasHeight" id="canvasHeight">

                        <button class="btn btn-success mt-3">Assinar Documento</button>
                    </form>


                </div>
            </main>

            <footer class="footer">
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>

        </div>
    </div>

    <div id="assinatura">Assinatura</div>

</body>

</html>








