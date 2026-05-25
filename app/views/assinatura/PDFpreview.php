<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
$appJsVersion = @filemtime($BASE_para_PATH . '/app/assets/js/app.js') ?: time();
require_once $BASE_para_PATH . '/api/legacy/checa-token.php';

$nomeArquivo = (string)($_POST['file'] ?? '');
$nomeDocumento = (string)($_POST['nomeDocumento'] ?? '');
$incluirCargo = (string)($_POST['incluir_cargo'] ?? '0');
$cargoPersonalizado = (string)($_POST['cargo_personalizado'] ?? '');

if ($nomeArquivo === '' || $nomeDocumento === '') {
    die('Dados nao recebidos. Volte ao envio do PDF.');
}

$pdfURL = $BASE_para_URL . '/app/storage/assinatura/originais/' . rawurlencode($nomeArquivo);
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
            cursor: pointer;
        }

        .page-canvas.page-canvas-active {
            outline: 3px solid rgba(13, 110, 253, 0.35);
            outline-offset: 4px;
        }

        #assinatura {
            width: 220px;
            height: 80px;
            background: rgba(0, 0, 0, 0.12);
            border: 2px dashed #000;
            position: absolute;
            top: 30px;
            left: 30px;
            cursor: grab;
            text-align: center;
            padding-top: 30px;
            font-weight: bold;
            font-size: 14px;
            display: none;
            user-select: none;
        }

        .preview-hint {
            margin: 12px 0 16px;
            color: #51607a;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        let pdfDoc = null;
        let activePageWrapper = null;
        let dragging = false;
        let offsetX = 0;
        let offsetY = 0;

        document.addEventListener('DOMContentLoaded', async () => {
            const pdfURL = "<?= htmlspecialchars($pdfURL, ENT_QUOTES, 'UTF-8') ?>";
            const viewer = document.getElementById('viewer');
            const box = document.getElementById('assinatura');
            const xposInput = document.getElementById('xpos');
            const yposInput = document.getElementById('ypos');
            const canvasWidthInput = document.getElementById('canvasWidth');
            const canvasHeightInput = document.getElementById('canvasHeight');
            const selectedPageInput = document.getElementById('selectedPage');
            const selectedPageLabel = document.getElementById('selectedPageLabel');

            pdfjsLib.GlobalWorkerOptions.workerSrc =
                'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

            pdfDoc = await pdfjsLib.getDocument(pdfURL).promise;
            viewer.appendChild(box);

            function updateSelectedPageLabel(pageNumber) {
                selectedPageLabel.textContent = String(pageNumber);
            }

            function activatePage(pageWrapper) {
                if (!pageWrapper) {
                    return;
                }

                if (activePageWrapper) {
                    activePageWrapper.classList.remove('page-canvas-active');
                }

                activePageWrapper = pageWrapper;
                activePageWrapper.classList.add('page-canvas-active');
                activePageWrapper.appendChild(box);

                selectedPageInput.value = pageWrapper.dataset.pageNumber || '1';
                canvasWidthInput.value = pageWrapper.dataset.canvasWidth || '';
                canvasHeightInput.value = pageWrapper.dataset.canvasHeight || '';
                updateSelectedPageLabel(selectedPageInput.value);
            }

            function setBoxPosition(x, y) {
                if (!activePageWrapper) {
                    return;
                }

                const maxX = Math.max(0, activePageWrapper.clientWidth - box.offsetWidth);
                const maxY = Math.max(0, activePageWrapper.clientHeight - box.offsetHeight);
                const clampedX = Math.min(Math.max(0, x), maxX);
                const clampedY = Math.min(Math.max(0, y), maxY);

                box.style.left = clampedX + 'px';
                box.style.top = clampedY + 'px';
                xposInput.value = clampedX;
                yposInput.value = clampedY;
            }

            function positionBoxOnPage(pageWrapper, x, y) {
                activatePage(pageWrapper);
                setBoxPosition(x, y);
            }

            for (let i = 1; i <= pdfDoc.numPages; i++) {
                const page = await pdfDoc.getPage(i);
                const viewport = page.getViewport({ scale: 1.3 });
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                const pageWrapper = document.createElement('div');
                pageWrapper.className = 'page-canvas';
                pageWrapper.dataset.pageNumber = String(i);
                pageWrapper.dataset.canvasWidth = String(canvas.width);
                pageWrapper.dataset.canvasHeight = String(canvas.height);
                pageWrapper.appendChild(canvas);
                viewer.appendChild(pageWrapper);

                await page.render({
                    canvasContext: ctx,
                    viewport: viewport
                }).promise;

                pageWrapper.addEventListener('click', (event) => {
                    if (event.target === box || box.contains(event.target)) {
                        return;
                    }

                    const rect = pageWrapper.getBoundingClientRect();
                    const clickX = event.clientX - rect.left;
                    const clickY = event.clientY - rect.top;
                    positionBoxOnPage(pageWrapper, clickX, clickY);
                });

                if (i === pdfDoc.numPages) {
                    box.style.display = 'block';
                    positionBoxOnPage(pageWrapper, 30, 30);
                }
            }

            box.addEventListener('mousedown', (event) => {
                dragging = true;
                offsetX = event.offsetX;
                offsetY = event.offsetY;
            });

            document.addEventListener('mouseup', () => {
                dragging = false;
            });

            document.addEventListener('mousemove', (event) => {
                if (!dragging || !activePageWrapper) {
                    return;
                }

                const rect = activePageWrapper.getBoundingClientRect();
                const x = event.clientX - rect.left - offsetX;
                const y = event.clientY - rect.top - offsetY;
                setBoxPosition(x, y);
            });
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
                    <p class="preview-hint">Clique na página desejada e arraste a caixa para definir onde a assinatura sera aplicada. Pagina selecionada: <strong id="selectedPageLabel">1</strong>.</p>
                    <div id="viewer"></div>

                    <form method="POST" action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/assinatura/pdf/finalizar/">
                        <input type="hidden" name="file" value="<?= htmlspecialchars($nomeArquivo, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="nomeDocumento" value="<?= htmlspecialchars($nomeDocumento, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="incluir_cargo" value="<?= htmlspecialchars($incluirCargo, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="cargo_personalizado" value="<?= htmlspecialchars($cargoPersonalizado, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="selected_page" id="selectedPage" value="1">
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
