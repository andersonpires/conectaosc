<?php
session_start();

// Agora trabalhamos SOMENTE via POST
$nomeArquivo = $_POST['file'] ?? '';
$nomeDocumento = $_POST['nomeDocumento'] ?? '';

if ($nomeArquivo === '' || $nomeDocumento === '') {
    die("Dados não recebidos. Volte ao envio do PDF.");
}

// Caminho público do PDF temporário
$pdfURL = $_SESSION['BASE_URL'] . "/app/assinatura/originais/" . $nomeArquivo;


?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <?php require_once $_SESSION['BASE_PATH'] . '/template/header.php'; ?>

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

                box.style.left = x + "px";
                box.style.top = y + "px";

                document.getElementById("xpos").value = x;
                document.getElementById("ypos").value = y;
            });
        });
    </script>

</head>

<body>

    <div class="wrapper">
        <?php require_once $_SESSION['BASE_PATH'] . '/template/menu.php'; ?>

        <div class="main">
            <?php require_once $_SESSION['BASE_PATH'] . '/template/topo.php'; ?>
            <script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/app.js"></script>

            <main class="content">
                <div class="container mt-4">

                    <h2>Posicione a assinatura digital</h2>

                    <div id="viewer"></div>

                    <form method="POST" action="PDFfinaliza.php">

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
                <?php require_once $_SESSION['BASE_PATH'] . '/template/footer.php'; ?>
            </footer>

        </div>
    </div>

    <div id="assinatura">Assinatura</div>

</body>

</html>
