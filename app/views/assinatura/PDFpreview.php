<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
$appJsVersion = @filemtime($BASE_para_PATH . '/app/assets/js/app.js') ?: time();
require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';

$nomeArquivo = (string)($_POST['file'] ?? '');
$nomeDocumento = (string)($_POST['nomeDocumento'] ?? '');
$incluirCargo = (string)($_POST['incluir_cargo'] ?? '0');
$cargoPersonalizado = trim((string)($_POST['cargo_personalizado'] ?? ''));

if ($nomeArquivo === '' || $nomeDocumento === '') {
    die('Dados não recebidos. Volte ao envio do PDF.');
}

$nomeCompleto = trim((string)(($_SESSION['Nome'] ?? '') . ' ' . ($_SESSION['Sobrenome'] ?? '')));
if ($nomeCompleto === '') {
    $nomeCompleto = 'Colaborador';
}

$cargoPadrao = '';
if ($incluirCargo === '1' && isset($pdo) && $pdo instanceof PDO) {
    $idColaborador = (int)($_SESSION['Cod'] ?? 0);
    if ($idColaborador > 0) {
        $stmtCargo = $pdo->prepare('SELECT COALESCE(Cargo, "") AS Cargo FROM tbUser WHERE IdColaborador = ? LIMIT 1');
        $stmtCargo->execute([$idColaborador]);
        $cargoPadrao = trim((string)($stmtCargo->fetchColumn() ?: ''));
    }
}

$cargoParaExibir = $incluirCargo === '1'
    ? ($cargoPersonalizado !== '' ? $cargoPersonalizado : $cargoPadrao)
    : '';
$nomeUpper = $nomeCompleto . ($cargoParaExibir !== '' ? ' - ' . $cargoParaExibir : '');
$nomeUpper = mb_strtoupper($nomeUpper, 'UTF-8');

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
            background: #f8fafc;
        }

        .page-canvas {
            position: relative;
            margin-bottom: 20px;
            cursor: pointer;
            background: #fff;
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.08);
        }

        .page-canvas.page-canvas-active {
            outline: 3px solid rgba(13, 110, 253, 0.35);
            outline-offset: 4px;
        }

        .preview-hint {
            margin: 12px 0 10px;
            color: #51607a;
        }

        .preview-toolbar {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .preview-toolbar .status {
            color: #51607a;
            font-size: 0.95rem;
        }

        #assinatura {
            width: 230px;
            height: 76px;
            position: absolute;
            top: 30px;
            left: 30px;
            cursor: grab;
            display: none;
            user-select: none;
            transform-origin: top left;
        }

        .sig-card {
            width: 100%;
            height: 100%;
            display: grid;
            grid-template-columns: 56px 1fr;
            gap: 10px;
            border: 2px dashed rgba(15, 23, 42, 0.48);
            background: rgba(255, 255, 255, 0.92);
            padding: 8px;
            border-radius: 8px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.12);
        }

        .sig-qr {
            border: 1px solid #111827;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
            color: #111827;
            background:
                linear-gradient(90deg, #111827 50%, transparent 50%) 0 0 / 10px 10px,
                linear-gradient(#111827 50%, transparent 50%) 0 0 / 10px 10px,
                #fff;
            background-blend-mode: difference;
            overflow: hidden;
        }

        .sig-text {
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: #111827;
        }

        .sig-script {
            font-family: "Brush Script MT", "Segoe Script", cursive;
            font-size: 21px;
            line-height: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 3px;
        }

        .sig-label {
            font-size: 10px;
            line-height: 1.1;
            margin-bottom: 3px;
        }

        .sig-upper {
            font-size: 9px;
            line-height: 1.1;
            font-weight: 700;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 4px;
        }

        .sig-code,
        .sig-auth {
            font-size: 8px;
            line-height: 1.1;
            color: #4b5563;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (max-width: 768px) {
            .preview-toolbar {
                align-items: stretch;
            }
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        let pdfDoc = null;
        let activePageWrapper = null;
        let dragging = false;
        let offsetX = 0;
        let offsetY = 0;
        const state = {
            scale: 1,
            rotation: 0
        };

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
            const scaleInput = document.getElementById('scaleInput');
            const rotationInput = document.getElementById('rotationInput');
            const scaleLabel = document.getElementById('scaleLabel');
            const rotationLabel = document.getElementById('rotationLabel');

            pdfjsLib.GlobalWorkerOptions.workerSrc =
                'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

            function normalizeRotation(value) {
                let normalized = value % 360;
                if (normalized < 0) {
                    normalized += 360;
                }
                return normalized;
            }

            function updateSelectedPageLabel(pageNumber) {
                selectedPageLabel.textContent = String(pageNumber);
            }

            function updateTransformLabels() {
                scaleInput.value = state.scale.toFixed(2);
                rotationInput.value = String(Math.round(state.rotation));
                scaleLabel.textContent = `${Math.round(state.scale * 100)}%`;
                rotationLabel.textContent = `${Math.round(state.rotation)}°`;
            }

            function getBaseDimensions() {
                return {
                    width: box.offsetWidth,
                    height: box.offsetHeight
                };
            }

            function getBoundingSize() {
                const base = getBaseDimensions();
                const width = base.width * state.scale;
                const height = base.height * state.scale;
                const radians = normalizeRotation(state.rotation) * Math.PI / 180;
                const cos = Math.abs(Math.cos(radians));
                const sin = Math.abs(Math.sin(radians));
                return {
                    width: (width * cos) + (height * sin),
                    height: (width * sin) + (height * cos)
                };
            }

            function applyTransform() {
                box.style.transform = `scale(${state.scale}) rotate(${state.rotation}deg)`;
                updateTransformLabels();
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

                const bounds = getBoundingSize();
                const maxX = Math.max(0, activePageWrapper.clientWidth - bounds.width);
                const maxY = Math.max(0, activePageWrapper.clientHeight - bounds.height);
                const clampedX = Math.min(Math.max(0, x), maxX);
                const clampedY = Math.min(Math.max(0, y), maxY);

                box.style.left = clampedX + 'px';
                box.style.top = clampedY + 'px';
                xposInput.value = clampedX.toFixed(2);
                yposInput.value = clampedY.toFixed(2);
            }

            function positionBoxOnPage(pageWrapper, x, y) {
                activatePage(pageWrapper);
                setBoxPosition(x, y);
            }

            function adjustScale(delta) {
                state.scale = Math.max(0.5, Math.min(2.5, Number((state.scale + delta).toFixed(2))));
                applyTransform();
                setBoxPosition(parseFloat(box.style.left) || 0, parseFloat(box.style.top) || 0);
            }

            function adjustRotation(delta) {
                state.rotation = normalizeRotation(state.rotation + delta);
                applyTransform();
                setBoxPosition(parseFloat(box.style.left) || 0, parseFloat(box.style.top) || 0);
            }

            document.getElementById('btnScaleDown').addEventListener('click', () => adjustScale(-0.1));
            document.getElementById('btnScaleUp').addEventListener('click', () => adjustScale(0.1));
            document.getElementById('btnRotateLeft').addEventListener('click', () => adjustRotation(-15));
            document.getElementById('btnRotateRight').addEventListener('click', () => adjustRotation(15));
            document.getElementById('btnResetSignature').addEventListener('click', () => {
                state.scale = 1;
                state.rotation = 0;
                applyTransform();
                setBoxPosition(30, 30);
            });

            pdfDoc = await pdfjsLib.getDocument(pdfURL).promise;
            viewer.appendChild(box);

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
                const boxRect = box.getBoundingClientRect();
                offsetX = event.clientX - boxRect.left;
                offsetY = event.clientY - boxRect.top;
                event.preventDefault();
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

            applyTransform();
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
                    <p class="preview-hint">Clique na página desejada, arraste o bloco e use os botões para aumentar, diminuir e girar a assinatura final. Página selecionada: <strong id="selectedPageLabel">1</strong>.</p>

                    <div class="preview-toolbar">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnScaleDown">Diminuir</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnScaleUp">Aumentar</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRotateLeft">Girar -15°</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRotateRight">Girar +15°</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetSignature">Resetar</button>
                        <span class="status">Escala: <strong id="scaleLabel">100%</strong></span>
                        <span class="status">Rotação: <strong id="rotationLabel">0°</strong></span>
                    </div>

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
                        <input type="hidden" name="scale" id="scaleInput" value="1.00">
                        <input type="hidden" name="rotation" id="rotationInput" value="0">
                        <button class="btn btn-success mt-3">Assinar documento</button>
                    </form>
                </div>
            </main>
            <footer class="footer">
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>
        </div>
    </div>

    <div id="assinatura">
        <div class="sig-card">
            <div class="sig-qr">QR</div>
            <div class="sig-text">
                <div class="sig-script"><?= htmlspecialchars($nomeCompleto, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="sig-label">Assinatura digital de:</div>
                <div class="sig-upper"><?= htmlspecialchars($nomeUpper, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="sig-code">Código para validação</div>
                <div class="sig-auth">Confira a autenticidade no validador</div>
            </div>
        </div>
    </div>
</body>
</html>
