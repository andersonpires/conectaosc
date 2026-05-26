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
$signerId = (int)($_POST['signer_id'] ?? 0);

if ($nomeArquivo === '' || $nomeDocumento === '') {
    die('Dados não recebidos. Volte ao envio do PDF.');
}

$nomeCompleto = trim((string)(($_SESSION['Nome'] ?? '') . ' ' . ($_SESSION['Sobrenome'] ?? '')));
if ($nomeCompleto === '') {
    $nomeCompleto = 'Colaborador';
}

$cargoPadrao = '';
if (isset($pdo) && $pdo instanceof PDO) {
    $idColaborador = $signerId > 0 ? $signerId : (int)($_SESSION['Cod'] ?? 0);
    if ($idColaborador > 0) {
        $stmtAssinante = $pdo->prepare('SELECT Nome, Sobrenome, COALESCE(Cargo, "") AS Cargo FROM tbUser WHERE IdColaborador = ? LIMIT 1');
        $stmtAssinante->execute([$idColaborador]);
        $assinante = $stmtAssinante->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($assinante) {
            $nomeCompleto = trim((string)(($assinante['Nome'] ?? '') . ' ' . ($assinante['Sobrenome'] ?? '')));
            if ($nomeCompleto === '') {
                $nomeCompleto = 'Colaborador';
            }
            $cargoPadrao = trim((string)($assinante['Cargo'] ?? ''));
        }
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
        .preview-shell {
            max-width: 1280px;
            margin: 0 auto;
        }

        .preview-hint {
            margin: 12px 0 10px;
            color: #51607a;
        }

        .preview-meta {
            margin-bottom: 14px;
            color: #1f2937;
            font-size: 0.96rem;
        }

        .preview-toolbar {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 16px;
            padding: 14px;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .preview-toolbar .status {
            color: #51607a;
            font-size: 0.95rem;
        }

        .preview-viewport {
            border: 1px solid #d9e1ea;
            border-radius: 18px;
            background: linear-gradient(180deg, #f8fafc 0%, #eef3f8 100%);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
            overflow: auto;
            max-height: calc(100vh - 260px);
            min-height: 55vh;
            padding: 12px;
            cursor: grab;
            touch-action: none;
        }

        .preview-viewport.dragging {
            cursor: grabbing;
        }

        #viewer {
            min-width: max-content;
        }

        .page-canvas {
            position: relative;
            margin: 0 auto 22px;
            cursor: pointer;
            background: #fff;
            box-shadow: 0 8px 28px rgba(15, 23, 42, 0.12);
        }

        .page-canvas.page-canvas-active {
            outline: 3px solid rgba(13, 110, 253, 0.35);
            outline-offset: 6px;
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
            z-index: 5;
            touch-action: none;
            will-change: left, top, transform;
        }

        .sig-card {
            width: 100%;
            height: 100%;
            display: grid;
            grid-template-columns: 56px 1fr;
            gap: 10px;
            border: 2px dashed rgba(15, 23, 42, 0.48);
            background: rgba(255, 255, 255, 0.96);
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
            .container.mt-4 {
                padding-left: 10px;
                padding-right: 10px;
            }

            .preview-toolbar {
                position: sticky;
                top: 8px;
                z-index: 20;
                align-items: stretch;
            }

            .preview-toolbar .btn {
                flex: 1 1 calc(50% - 10px);
                min-height: 44px;
            }

            .preview-toolbar .status {
                width: 100%;
                font-size: 0.9rem;
            }

            .preview-meta {
                font-size: 0.92rem;
            }

            .preview-viewport {
                max-height: calc(100vh - 240px);
                min-height: 60vh;
                padding: 8px;
                border-radius: 14px;
            }

            #assinatura {
                width: 198px;
                height: 72px;
            }

            .sig-card {
                grid-template-columns: 48px 1fr;
                gap: 8px;
                padding: 7px;
            }
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        let pdfDoc = null;
        let activePageWrapper = null;
        let draggingSignature = false;
        let draggingViewport = false;
        let signatureOffsetX = 0;
        let signatureOffsetY = 0;
        let viewportDragStartX = 0;
        let viewportDragStartY = 0;
        let viewportScrollLeft = 0;
        let viewportScrollTop = 0;
        let renderToken = 0;
        let activePointerId = null;

        const state = {
            scale: 1,
            rotation: 0,
            documentZoom: 1.15,
            selectedPage: 1,
            relativeX: 30 / 230,
            relativeY: 30 / 76
        };

        document.addEventListener('DOMContentLoaded', async () => {
            const pdfURL = "<?= htmlspecialchars($pdfURL, ENT_QUOTES, 'UTF-8') ?>";
            const viewer = document.getElementById('viewer');
            const viewport = document.getElementById('viewerViewport');
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
            const documentZoomLabel = document.getElementById('documentZoomLabel');
            const totalPagesLabel = document.getElementById('totalPagesLabel');
            let pageWrappers = new Map();

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

            function focusSelectedPage(pageWrapper, behavior = 'smooth') {
                if (!pageWrapper) {
                    return;
                }

                const top = Math.max(0, pageWrapper.offsetTop - 18);
                const left = Math.max(0, pageWrapper.offsetLeft - 18);
                viewport.scrollTo({
                    top,
                    left,
                    behavior
                });
            }

            function updateTransformLabels() {
                scaleInput.value = state.scale.toFixed(2);
                rotationInput.value = String(Math.round(state.rotation));
                scaleLabel.textContent = `${Math.round(state.scale * 100)}%`;
                rotationLabel.textContent = `${Math.round(state.rotation)}°`;
                documentZoomLabel.textContent = `${Math.round(state.documentZoom * 100)}%`;
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

            function applySignatureTransform() {
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
                state.selectedPage = Number(pageWrapper.dataset.pageNumber || '1');
                selectedPageInput.value = String(state.selectedPage);
                canvasWidthInput.value = pageWrapper.dataset.canvasWidth || '';
                canvasHeightInput.value = pageWrapper.dataset.canvasHeight || '';
                updateSelectedPageLabel(state.selectedPage);
            }

            function updateRelativePositionFromCurrent() {
                if (!activePageWrapper) {
                    return;
                }
                const width = Math.max(1, activePageWrapper.clientWidth);
                const height = Math.max(1, activePageWrapper.clientHeight);
                state.relativeX = (parseFloat(box.style.left) || 0) / width;
                state.relativeY = (parseFloat(box.style.top) || 0) / height;
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
                updateRelativePositionFromCurrent();
            }

            function setBoxPositionFromRelative() {
                if (!activePageWrapper) {
                    return;
                }
                setBoxPosition(
                    state.relativeX * activePageWrapper.clientWidth,
                    state.relativeY * activePageWrapper.clientHeight
                );
            }

            function positionBoxOnPage(pageWrapper, x, y) {
                activatePage(pageWrapper);
                setBoxPosition(x, y);
                focusSelectedPage(pageWrapper);
            }

            function adjustSignatureScale(delta) {
                state.scale = Math.max(0.5, Math.min(2.5, Number((state.scale + delta).toFixed(2))));
                applySignatureTransform();
                setBoxPositionFromRelative();
            }

            function adjustRotation(delta) {
                state.rotation = normalizeRotation(state.rotation + delta);
                applySignatureTransform();
                setBoxPositionFromRelative();
            }

            async function renderDocument(zoom) {
                if (!pdfDoc) {
                    return;
                }

                const token = ++renderToken;
                pageWrappers = new Map();
                viewer.innerHTML = '';
                viewer.appendChild(box);

                for (let i = 1; i <= pdfDoc.numPages; i++) {
                    const page = await pdfDoc.getPage(i);
                    const viewportScale = page.getViewport({ scale: zoom });
                    if (token !== renderToken) {
                        return;
                    }

                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    canvas.height = viewportScale.height;
                    canvas.width = viewportScale.width;

                    const pageWrapper = document.createElement('div');
                    pageWrapper.className = 'page-canvas';
                    pageWrapper.dataset.pageNumber = String(i);
                    pageWrapper.dataset.canvasWidth = String(canvas.width);
                    pageWrapper.dataset.canvasHeight = String(canvas.height);
                    pageWrapper.appendChild(canvas);
                    viewer.appendChild(pageWrapper);
                    pageWrappers.set(i, pageWrapper);

                    await page.render({
                        canvasContext: ctx,
                        viewport: viewportScale
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
                }

                const targetPage = pageWrappers.get(state.selectedPage) || pageWrappers.get(pdfDoc.numPages);
                if (targetPage) {
                    box.style.display = 'block';
                    activatePage(targetPage);
                    setBoxPositionFromRelative();
                    focusSelectedPage(targetPage, 'auto');
                }

                updateTransformLabels();
            }

            async function fitDocumentToWidth() {
                if (!pdfDoc) {
                    return;
                }
                const firstPage = await pdfDoc.getPage(1);
                const viewportScale1 = firstPage.getViewport({ scale: 1 });
                const availableWidth = Math.max(320, viewport.clientWidth - 30);
                state.documentZoom = Number((availableWidth / viewportScale1.width).toFixed(2));
                state.documentZoom = Math.max(0.55, Math.min(2.2, state.documentZoom));
                await renderDocument(state.documentZoom);
            }

            async function fitDocumentToPage() {
                if (!pdfDoc) {
                    return;
                }
                const firstPage = await pdfDoc.getPage(1);
                const viewportScale1 = firstPage.getViewport({ scale: 1 });
                const availableWidth = Math.max(280, viewport.clientWidth - 28);
                const availableHeight = Math.max(280, viewport.clientHeight - 28);
                const widthScale = availableWidth / viewportScale1.width;
                const heightScale = availableHeight / viewportScale1.height;
                state.documentZoom = Number(Math.min(widthScale, heightScale).toFixed(2));
                state.documentZoom = Math.max(0.4, Math.min(2.2, state.documentZoom));
                await renderDocument(state.documentZoom);
            }

            function goToPage(nextPage) {
                if (!pdfDoc) {
                    return;
                }
                const pageNumber = Math.max(1, Math.min(pdfDoc.numPages, nextPage));
                const targetPage = pageWrappers.get(pageNumber);
                if (!targetPage) {
                    return;
                }
                activatePage(targetPage);
                setBoxPositionFromRelative();
                focusSelectedPage(targetPage);
            }

            document.getElementById('btnScaleDown').addEventListener('click', () => adjustSignatureScale(-0.1));
            document.getElementById('btnScaleUp').addEventListener('click', () => adjustSignatureScale(0.1));
            document.getElementById('btnRotateLeft').addEventListener('click', () => adjustRotation(-15));
            document.getElementById('btnRotateRight').addEventListener('click', () => adjustRotation(15));
            document.getElementById('btnResetSignature').addEventListener('click', () => {
                state.scale = 1;
                state.rotation = 0;
                state.relativeX = 30 / 230;
                state.relativeY = 30 / 76;
                applySignatureTransform();
                setBoxPositionFromRelative();
            });
            document.getElementById('btnDocZoomOut').addEventListener('click', async () => {
                state.documentZoom = Math.max(0.55, Number((state.documentZoom - 0.1).toFixed(2)));
                await renderDocument(state.documentZoom);
            });
            document.getElementById('btnDocZoomIn').addEventListener('click', async () => {
                state.documentZoom = Math.min(2.2, Number((state.documentZoom + 0.1).toFixed(2)));
                await renderDocument(state.documentZoom);
            });
            document.getElementById('btnDocFit').addEventListener('click', async () => {
                await fitDocumentToWidth();
            });
            document.getElementById('btnDocFitPage').addEventListener('click', async () => {
                await fitDocumentToPage();
            });
            document.getElementById('btnPrevPage').addEventListener('click', () => {
                goToPage(state.selectedPage - 1);
            });
            document.getElementById('btnNextPage').addEventListener('click', () => {
                goToPage(state.selectedPage + 1);
            });

            viewport.addEventListener('pointerdown', (event) => {
                if (event.target === box || box.contains(event.target) || event.pointerType === 'mouse' && event.button !== 0) {
                    return;
                }
                draggingViewport = true;
                activePointerId = event.pointerId;
                viewport.classList.add('dragging');
                viewportDragStartX = event.clientX;
                viewportDragStartY = event.clientY;
                viewportScrollLeft = viewport.scrollLeft;
                viewportScrollTop = viewport.scrollTop;
                event.preventDefault();
            });

            document.addEventListener('pointerup', (event) => {
                if (activePointerId !== null && event.pointerId !== activePointerId) {
                    return;
                }
                draggingSignature = false;
                draggingViewport = false;
                activePointerId = null;
                viewport.classList.remove('dragging');
            });

            document.addEventListener('pointercancel', () => {
                draggingSignature = false;
                draggingViewport = false;
                activePointerId = null;
                viewport.classList.remove('dragging');
            });

            document.addEventListener('pointermove', (event) => {
                if (activePointerId !== null && event.pointerId !== activePointerId) {
                    return;
                }

                if (draggingSignature && activePageWrapper) {
                    const rect = activePageWrapper.getBoundingClientRect();
                    const x = event.clientX - rect.left - signatureOffsetX;
                    const y = event.clientY - rect.top - signatureOffsetY;
                    setBoxPosition(x, y);
                    event.preventDefault();
                    return;
                }

                if (draggingViewport) {
                    viewport.scrollLeft = viewportScrollLeft - (event.clientX - viewportDragStartX);
                    viewport.scrollTop = viewportScrollTop - (event.clientY - viewportDragStartY);
                    event.preventDefault();
                }
            });

            box.addEventListener('pointerdown', (event) => {
                if (event.pointerType === 'mouse' && event.button !== 0) {
                    return;
                }
                draggingSignature = true;
                activePointerId = event.pointerId;
                const boxRect = box.getBoundingClientRect();
                signatureOffsetX = event.clientX - boxRect.left;
                signatureOffsetY = event.clientY - boxRect.top;
                event.preventDefault();
                event.stopPropagation();
            });

            pdfjsLib.GlobalWorkerOptions.workerSrc =
                'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
            pdfDoc = await pdfjsLib.getDocument(pdfURL).promise;
            totalPagesLabel.textContent = String(pdfDoc.numPages);
            applySignatureTransform();
            await fitDocumentToWidth();

            window.addEventListener('resize', () => {
                clearTimeout(window.__previewResizeTimer);
                window.__previewResizeTimer = setTimeout(() => {
                    fitDocumentToWidth();
                }, 120);
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
                    <div class="preview-shell">
                        <h2>Posicione a assinatura digital</h2>
                        <p class="preview-hint">Clique na página desejada, arraste o bloco e use os controles para ajustar a assinatura final. Em celular, arraste o documento com o dedo e use os botões de zoom para enquadrar a página antes de posicionar a assinatura.</p>
                        <div class="preview-meta">Assinando como: <strong><?= htmlspecialchars($nomeCompleto, ENT_QUOTES, 'UTF-8') ?></strong><?= $cargoParaExibir !== '' ? ' <span class="text-muted">(' . htmlspecialchars($cargoParaExibir, ENT_QUOTES, 'UTF-8') . ')</span>' : '' ?></div>

                        <div class="preview-toolbar">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnPrevPage">Página anterior</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnNextPage">Próxima página</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnDocZoomOut">Documento -</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnDocZoomIn">Documento +</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnDocFit">Ajustar à largura</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnDocFitPage">Ver página inteira</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnScaleDown">Assinatura -</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnScaleUp">Assinatura +</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRotateLeft">Girar -15°</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRotateRight">Girar +15°</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetSignature">Resetar</button>
                            <span class="status">Página selecionada: <strong id="selectedPageLabel">1</strong> de <strong id="totalPagesLabel">1</strong></span>
                            <span class="status">Zoom do documento: <strong id="documentZoomLabel">100%</strong></span>
                            <span class="status">Escala da assinatura: <strong id="scaleLabel">100%</strong></span>
                            <span class="status">Rotação: <strong id="rotationLabel">0°</strong></span>
                        </div>

                        <div class="preview-viewport" id="viewerViewport">
                            <div id="viewer"></div>
                        </div>

                        <form method="POST" action="<?php echo rtrim((string)$BASE_para_URL, '/'); ?>/assinatura/pdf/finalizar/">
                            <input type="hidden" name="file" value="<?= htmlspecialchars($nomeArquivo, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="nomeDocumento" value="<?= htmlspecialchars($nomeDocumento, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="incluir_cargo" value="<?= htmlspecialchars($incluirCargo, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="cargo_personalizado" value="<?= htmlspecialchars($cargoPersonalizado, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="signer_id" value="<?= (int)$signerId ?>">
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
