<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL  = $runtime['base_para_url'];

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    header('Location: ' . rtrim((string)($BASE_para_URL ?? ''), '/') . '/login/');
    exit();
}

require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';
require_once $BASE_para_PATH . '/api/lib/tcpdf/tcpdf.php';
require_once $BASE_para_PATH . '/api/lib/tcpdf/fpdi/autoload.php';
require_once __DIR__ . '/contratoLoteHelper.php';

$idCurso = isset($_GET['curso']) ? (int)$_GET['curso'] : 0;
$idTurma = isset($_GET['turma']) ? (int)$_GET['turma'] : 0;
$modo    = (isset($_GET['modo']) && $_GET['modo'] === 'zip') ? 'zip' : 'individual';

if ($idCurso <= 0) {
    http_response_code(400);
    echo '<p style="color:red;font-family:sans-serif;padding:2rem;">Parâmetro de curso inválido.</p>';
    exit;
}

// Busca o primeiro aluno ativo da turma/curso para o preview
if ($idTurma > 0) {
    $stmtPrev = $pdo->prepare("
        SELECT m.IdMatricula, a.Nome
          FROM tbMatricula m
          JOIN tbAluno a ON m.IdUsuario = a.IdUsuario
          JOIN tbCurso c ON m.IdCurso = c.IdCurso
          JOIN tbTurma t ON m.IdTurma = t.IdTurma
         WHERE c.IdCurso = ? AND t.IdTurma = ?
           AND m.Habilitado = 1 AND a.Habilitado = 1
           AND c.Habilitado = 1 AND t.Habilitado = 1
      ORDER BY a.Nome LIMIT 1
    ");
    $stmtPrev->execute([$idCurso, $idTurma]);
} else {
    $stmtPrev = $pdo->prepare("
        SELECT m.IdMatricula, a.Nome
          FROM tbMatricula m
          JOIN tbAluno a ON m.IdUsuario = a.IdUsuario
          JOIN tbCurso c ON m.IdCurso = c.IdCurso
          JOIN tbTurma t ON m.IdTurma = t.IdTurma
         WHERE c.IdCurso = ?
           AND m.Habilitado = 1 AND a.Habilitado = 1
           AND c.Habilitado = 1 AND t.Habilitado = 1
      ORDER BY a.Nome LIMIT 1
    ");
    $stmtPrev->execute([$idCurso]);
}
$primeiroAluno = $stmtPrev->fetch(PDO::FETCH_ASSOC);

if (!$primeiroAluno) {
    http_response_code(404);
    echo '<p style="color:red;font-family:sans-serif;padding:2rem;">Nenhum aluno ativo encontrado para gerar o preview.</p>';
    exit;
}

// Dados completos do primeiro aluno
$sqlDados = $pdo->prepare("
    SELECT a.*, c.NomeCurso, t.NomeTurma, m.IdMatricula, m.vData, p.LogoProjeto, p.TermosContrato
      FROM tbMatricula m
      JOIN tbAluno a ON m.IdUsuario = a.IdUsuario
      JOIN tbCurso c ON m.IdCurso = c.IdCurso
      JOIN tbTurma t ON m.IdTurma = t.IdTurma
 LEFT JOIN tbProjeto p ON c.IdProjeto = p.IdProjeto
     WHERE m.IdMatricula = ? AND m.Habilitado = 1 AND a.Habilitado = 1 LIMIT 1
");
$sqlDados->execute([$primeiroAluno['IdMatricula']]);
$dados = $sqlDados->fetch(PDO::FETCH_ASSOC);

$configAssinatura = loteContratoCarregarConfig($pdo, $BASE_para_PATH);
$responsavelSistema  = (string)($configAssinatura['responsavelSistema'] ?? 'Instituto Tecnológico e Vocacional Avançado - ITEVA');
$linhaDirigenteCargo = (string)($configAssinatura['linhaDirigenteCargo'] ?? '');
$nomeDirigente       = (string)($configAssinatura['nomeDirigente'] ?? 'Dirigente');

// Gera PDF em memória para o preview
$logoProjetoPath = loteContratoResolveProjectLogoPath((string)($dados['LogoProjeto'] ?? ''), $BASE_para_PATH);

$pdf = new LoteContratoPDF();
$pdf->logoPath        = $logoProjetoPath;
$pdf->logoSistemaPath = (string)($configAssinatura['logoSistemaPath'] ?? '');
$pdf->SetCreator('Conecta OSC');
$pdf->SetAuthor('Conecta OSC');
$pdf->SetTitle('Preview - ' . (string)($dados['Nome'] ?? ''));
$pdf->SetMargins(15, 40, 15);
$pdf->SetHeaderMargin(0);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 11);

$pdf->writeHTML(loteContratoGerarHtmlCorpo($dados), true, false, true, false, '');

$yAfterBody    = (float)$pdf->GetY();
$pageAfterBody = (int)$pdf->getPage();
$topMargin     = (float)$pdf->getMargins()['top'];
$bottomLimit   = $pdf->getPageHeight() - $pdf->getBreakMargin();
$sigBlockTotal = 60.0;

if ($yAfterBody + $sigBlockTotal > $bottomLimit) {
    $pdf->AddPage();
    $paginaComEspaco = (int)$pdf->getPage();
    $ySignaturaStart = $topMargin;
} else {
    $paginaComEspaco = $pageAfterBody;
    $ySignaturaStart = $yAfterBody;
}

loteContratoDesenharLinhasAssinatura(
    $pdf,
    $dados,
    $responsavelSistema,
    $linhaDirigenteCargo,
    $ySignaturaStart + 24.0
);

$totalPages = $pdf->getNumPages();
$pdfBase64  = base64_encode($pdf->Output('', 'S'));

// URL de destino para a geração em lote (sem sig_x/sig_y — serão adicionados pelo JS)
$batchBaseUrl = rtrim((string)$BASE_para_URL, '/') . '/matriculas/contratos/curso'
    . '?curso=' . $idCurso
    . '&turma=' . $idTurma
    . '&modo='  . urlencode($modo)
    . '&assinar=1';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Posicionar assinatura digital</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", Arial, sans-serif; background: #f1f5f9; color: #0f172a; }

        .layout { display: flex; min-height: 100vh; }

        /* Painel lateral */
        .sidebar {
            width: 320px; flex-shrink: 0;
            background: #fff; border-right: 1px solid #e2e8f0;
            padding: 24px 20px; display: flex; flex-direction: column; gap: 16px;
            position: sticky; top: 0; height: 100vh; overflow-y: auto;
        }
        .sidebar h1 { font-size: 1.1rem; margin: 0 0 4px; }
        .sidebar p  { font-size: 0.85rem; color: #64748b; margin: 0; line-height: 1.5; }

        .instrucao {
            background: #eff6ff; border: 1px solid #bfdbfe;
            border-radius: 10px; padding: 12px 14px; font-size: 0.82rem;
            color: #1e40af; line-height: 1.6;
        }
        .instrucao b { display: block; margin-bottom: 4px; }

        .overlay-preview {
            background: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 10px; padding: 10px 12px;
        }
        .overlay-preview-title { font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 8px; }

        .btn-confirmar {
            width: 100%; padding: 14px;
            background: #2563eb; color: #fff; border: none; border-radius: 10px;
            font-size: 0.95rem; font-weight: 600; cursor: pointer;
            margin-top: auto;
        }
        .btn-confirmar:hover { background: #1d4ed8; }

        .coords { font-size: 0.78rem; color: #94a3b8; text-align: center; }

        /* Área do viewer */
        .viewer-area {
            flex: 1; padding: 24px; display: flex; flex-direction: column; align-items: center; gap: 12px;
            overflow-y: auto;
        }

        .page-wrapper {
            position: relative; display: inline-block;
            box-shadow: 0 4px 20px rgba(0,0,0,.15); border-radius: 2px; background: #fff;
        }
        .page-wrapper canvas { display: block; }

        /* Overlay de assinatura */
        #sig-overlay {
            position: absolute;
            cursor: grab;
            user-select: none;
            background: rgba(255,255,255,0.92);
            border: 2px dashed #2563eb;
            border-radius: 8px;
            padding: 7px 10px;
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 180px;
            touch-action: none;
        }
        #sig-overlay.dragging { cursor: grabbing; opacity: 0.85; }
        .sig-row { display: flex; align-items: flex-start; gap: 8px; }
        .sig-qr {
            width: 44px; height: 44px; flex-shrink: 0;
            background: #e2e8f0; border: 1px solid #94a3b8;
            display: flex; align-items: center; justify-content: center;
            border-radius: 3px; font-size: 9px; color: #64748b; font-weight: 600;
        }
        .sig-text { display: flex; flex-direction: column; gap: 1px; }
        .sig-name-cursive { font-family: cursive; font-size: 13px; color: #0f172a; }
        .sig-label { font-size: 9px; color: #64748b; }
        .sig-name-upper { font-size: 10px; font-weight: 700; color: #0f172a; }
        .sig-code { font-size: 9px; color: #475569; }

        .loading-msg { color: #64748b; font-size: 0.9rem; padding: 40px; text-align: center; }
    </style>
</head>
<body>
<div class="layout">

    <!-- Painel lateral -->
    <aside class="sidebar">
        <div>
            <h1>Posicionar assinatura digital</h1>
            <p>Pré-visualização de: <strong><?= htmlspecialchars((string)($dados['Nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></p>
        </div>

        <div class="instrucao">
            <b>Como usar:</b>
            Arraste o bloco azul pontilhado para o local desejado na última página do contrato,<br>
            ou <strong>clique</strong> em qualquer ponto da página para mover o bloco até lá.<br><br>
            Quando estiver satisfeito, clique em <strong>Confirmar e Gerar Todos</strong>.
        </div>

        <div class="overlay-preview">
            <div class="overlay-preview-title">Prévia do bloco de assinatura</div>
            <div class="sig-row">
                <div class="sig-qr">QR</div>
                <div class="sig-text">
                    <span class="sig-name-cursive"><?= htmlspecialchars($nomeDirigente, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="sig-label">Assinatura digital de:</span>
                    <span class="sig-name-upper"><?= htmlspecialchars(mb_strtoupper($nomeDirigente, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>
            <div class="sig-code">Código para validação: XXXXXX</div>
        </div>

        <p class="coords" id="coords-display">Posição: —</p>

        <button class="btn-confirmar" id="btnConfirmar">
            ✔ Confirmar e Gerar Todos
        </button>
    </aside>

    <!-- Viewer PDF -->
    <div class="viewer-area" id="viewer-area">
        <div class="loading-msg" id="loading-msg">Carregando prévia do contrato…</div>
    </div>

</div>

<!-- Overlay do bloco de assinatura (será movido para dentro do wrapper da última página) -->
<div id="sig-overlay" style="display:none;">
    <div class="sig-row">
        <div class="sig-qr">QR</div>
        <div class="sig-text">
            <span class="sig-name-cursive"><?= htmlspecialchars($nomeDirigente, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="sig-label">Assinatura digital de:</span>
            <span class="sig-name-upper"><?= htmlspecialchars(mb_strtoupper($nomeDirigente, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>
    <div class="sig-code">Código para validação: XXXXXX</div>
</div>

<script>
pdfjsLib.GlobalWorkerOptions.workerSrc =
    'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

const PDF_BASE64      = <?= json_encode($pdfBase64) ?>;
const TOTAL_PAGES     = <?= (int)$totalPages ?>;
const PAGINA_ESPACO   = <?= (int)$paginaComEspaco ?>;
const Y_SPACER_MM     = <?= json_encode($ySignaturaStart) ?>;
const BATCH_BASE_URL  = <?= json_encode($batchBaseUrl) ?>;
// mm = pts * (25.4/72);  pts = css_px / SCALE
// => mm = css_px / SCALE * (25.4/72) = css_px * 25.4 / (SCALE * 72)

let RENDER_SCALE = 1.4;
let lastPageViewport = null;
let lastPageWrapper  = null;

const overlay     = document.getElementById('sig-overlay');
const coordsDisp  = document.getElementById('coords-display');
const viewerArea  = document.getElementById('viewer-area');
const loadingMsg  = document.getElementById('loading-msg');

// ── Drag ──────────────────────────────────────────────────────────────
let isDragging = false, dragOffX = 0, dragOffY = 0;

overlay.addEventListener('mousedown', startDrag);
overlay.addEventListener('touchstart', (e) => startDrag(e.touches[0]), { passive: true });

function startDrag(e) {
    isDragging = true;
    const rect = overlay.getBoundingClientRect();
    dragOffX = (e.clientX || e.pageX) - rect.left;
    dragOffY = (e.clientY || e.pageY) - rect.top;
    overlay.classList.add('dragging');
}

document.addEventListener('mousemove', onMove);
document.addEventListener('touchmove', (e) => onMove(e.touches[0]), { passive: true });

function onMove(e) {
    if (!isDragging || !lastPageWrapper) return;
    const wr  = lastPageWrapper.getBoundingClientRect();
    const cx  = (e.clientX || e.pageX) - wr.left - dragOffX;
    const cy  = (e.clientY || e.pageY) - wr.top  - dragOffY;
    moveTo(cx, cy);
}

document.addEventListener('mouseup',  endDrag);
document.addEventListener('touchend', endDrag);
function endDrag() {
    isDragging = false;
    overlay.classList.remove('dragging');
}

// ── Clique na última página para mover ────────────────────────────────
function bindPageClick(canvas, wrapper) {
    canvas.addEventListener('click', function (e) {
        if (isDragging) return;
        const wr = wrapper.getBoundingClientRect();
        const cx = e.clientX - wr.left - overlay.offsetWidth  / 2;
        const cy = e.clientY - wr.top  - overlay.offsetHeight / 2;
        moveTo(cx, cy);
    });
}

// ── Movimento com clamp dentro da página ──────────────────────────────
function moveTo(left, top) {
    if (!lastPageWrapper) return;
    const maxLeft = lastPageWrapper.offsetWidth  - overlay.offsetWidth;
    const maxTop  = lastPageWrapper.offsetHeight - overlay.offsetHeight;
    const l = Math.max(0, Math.min(maxLeft, left));
    const t = Math.max(0, Math.min(maxTop,  top));
    overlay.style.left = l + 'px';
    overlay.style.top  = t + 'px';
    updateCoords(l, t);
}

function updateCoords(leftPx, topPx) {
    const mm = pxToMm(leftPx, topPx);
    coordsDisp.textContent = 'X: ' + mm.x.toFixed(1) + ' mm  |  Y: ' + mm.y.toFixed(1) + ' mm';
}

function pxToMm(leftPx, topPx) {
    const ptPerMm = 72 / 25.4;
    return {
        x: leftPx / (RENDER_SCALE * ptPerMm),
        y: topPx  / (RENDER_SCALE * ptPerMm),
    };
}

// ── Posição padrão ─────────────────────────────────────────────────────
function setDefaultPosition() {
    if (!lastPageViewport || !lastPageWrapper) return;
    const ptPerMm = 72 / 25.4;

    // Largura do grupo em mm: QR(18) + gap(3) + texto(98) = 119mm
    const groupWidthMm  = 119.0;
    const groupHeightMm = 19.0;

    // Centra horizontalmente na página
    const pageWidthMm = lastPageViewport.width / (ptPerMm * RENDER_SCALE);
    const defaultLeft = Math.max(0, (pageWidthMm - groupWidthMm) / 2 * ptPerMm * RENDER_SCALE);

    let defaultTop;
    if (Y_SPACER_MM !== null) {
        // Y_SPACER_MM = início da área de 24 mm reservada para o QR;
        // +7 px compensa o padding-top do overlay para que o centro visual do QR
        // coincida com o centro real do QR no PDF final.
        defaultTop = (Y_SPACER_MM + 2.5) * ptPerMm * RENDER_SCALE + 7;
    } else {
        defaultTop = lastPageViewport.height * 0.68;
    }

    moveTo(defaultLeft, defaultTop);
}

// ── Renderização PDF ───────────────────────────────────────────────────
async function renderPdf() {
    const raw   = atob(PDF_BASE64);
    const bytes = new Uint8Array(raw.length);
    for (let i = 0; i < raw.length; i++) bytes[i] = raw.charCodeAt(i);

    const pdfDoc = await pdfjsLib.getDocument({ data: bytes }).promise;
    loadingMsg.remove();

    // Calcula escala para caber na largura disponível
    const firstPage = await pdfDoc.getPage(1);
    const vp0 = firstPage.getViewport({ scale: 1 });
    const maxW = viewerArea.clientWidth - 48;
    RENDER_SCALE = Math.min(1.6, Math.max(0.8, maxW / vp0.width));

    for (let n = 1; n <= pdfDoc.numPages; n++) {
        const page     = await pdfDoc.getPage(n);
        const viewport = page.getViewport({ scale: RENDER_SCALE });

        const wrapper = document.createElement('div');
        wrapper.className = 'page-wrapper';
        wrapper.style.width  = viewport.width  + 'px';
        wrapper.style.height = viewport.height + 'px';

        const canvas = document.createElement('canvas');
        canvas.width  = Math.round(viewport.width  * (window.devicePixelRatio || 1));
        canvas.height = Math.round(viewport.height * (window.devicePixelRatio || 1));
        canvas.style.width  = viewport.width  + 'px';
        canvas.style.height = viewport.height + 'px';
        wrapper.appendChild(canvas);

        viewerArea.appendChild(wrapper);

        const ctx = canvas.getContext('2d');
        ctx.scale(window.devicePixelRatio || 1, window.devicePixelRatio || 1);
        await page.render({ canvasContext: ctx, viewport }).promise;

        if (n === PAGINA_ESPACO) {
            lastPageViewport = viewport;
            lastPageWrapper  = wrapper;

            overlay.style.display = '';
            wrapper.appendChild(overlay);
            bindPageClick(canvas, wrapper);

            requestAnimationFrame(() => setDefaultPosition());
        }
    }
}

// ── Confirmar posição ─────────────────────────────────────────────────
document.getElementById('btnConfirmar').addEventListener('click', function () {
    const leftPx = parseFloat(overlay.style.left || '0');
    const topPx  = parseFloat(overlay.style.top  || '0');

    // Subtrai o padding-top do overlay (7 px) para que a posição gravada
    // corresponda ao centro visual do QR — e não ao canto superior do overlay.
    const OVERLAY_PAD_TOP_PX = 7;
    const mm = pxToMm(leftPx, Math.max(0, topPx - OVERLAY_PAD_TOP_PX));

    // Envia offset relativo ao início da área do QR (Y_SPACER_MM).
    const sigYRel = mm.y - Y_SPACER_MM;

    const url = BATCH_BASE_URL
        + '&sig_x='     + mm.x.toFixed(3)
        + '&sig_y_rel=' + sigYRel.toFixed(3);

    window.location.href = url;
});

renderPdf().catch(err => {
    loadingMsg.textContent = 'Erro ao carregar prévia: ' + (err.message || err);
    if (!loadingMsg.parentNode) viewerArea.appendChild(loadingMsg);
});
</script>
</body>
</html>
