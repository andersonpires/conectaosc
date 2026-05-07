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

$idUsuario = isset($_GET['aluno']) ? (int)$_GET['aluno'] : 0;
$idTurma   = isset($_GET['turma']) ? (int)$_GET['turma'] : 0;

if ($idUsuario <= 0 || $idTurma <= 0) {
    http_response_code(400);
    echo '<p style="color:red;font-family:sans-serif;padding:2rem;">Parâmetros inválidos.</p>';
    exit;
}

$sql = $pdo->prepare("
    SELECT a.*, c.NomeCurso, t.NomeTurma, m.IdMatricula, m.vData, p.LogoProjeto, p.TermosContrato
      FROM tbMatricula m
      JOIN tbAluno a ON m.IdUsuario = a.IdUsuario
      JOIN tbCurso c ON m.IdCurso = c.IdCurso
      JOIN tbTurma t ON m.IdTurma = t.IdTurma
 LEFT JOIN tbProjeto p ON c.IdProjeto = p.IdProjeto
     WHERE m.IdUsuario = ? AND m.IdTurma = ?
     LIMIT 1
");
$sql->execute([$idUsuario, $idTurma]);
$dados = $sql->fetch(PDO::FETCH_ASSOC);

if (!$dados) {
    http_response_code(404);
    echo '<p style="color:red;font-family:sans-serif;padding:2rem;">Aluno não encontrado nesta turma.</p>';
    exit;
}

$configAssinatura    = loteContratoCarregarConfig($pdo, $BASE_para_PATH);
$responsavelSistema  = (string)($configAssinatura['responsavelSistema']  ?? 'Instituto Tecnológico e Vocacional Avançado - ITEVA');
$linhaDirigenteCargo = (string)($configAssinatura['linhaDirigenteCargo'] ?? '');
$nomeDirigente       = (string)($configAssinatura['nomeDirigente']       ?? 'Dirigente');

// Monta HTML do corpo igual ao gerarContrato.php
$responsavel  = (string)($dados['NomeResp1']    ?? '');
$cpfResp      = (string)($dados['CpfResp1']     ?? '');
$telefoneResp = (string)($dados['TelefoneResp1'] ?? ($dados['WhatsAppResp1'] ?? ''));
$nome         = (string)($dados['Nome']          ?? '');

$responsavelRows = '';
if (trim($responsavel)  !== '') $responsavelRows .= '<tr><td><b>Nome</b></td><td>'    . htmlspecialchars($responsavel)  . '</td></tr>';
if (trim($cpfResp)      !== '') $responsavelRows .= '<tr><td><b>CPF</b></td><td>'     . htmlspecialchars($cpfResp)      . '</td></tr>';
if (trim($telefoneResp) !== '') $responsavelRows .= '<tr><td><b>Contato</b></td><td>' . htmlspecialchars($telefoneResp) . '</td></tr>';
$responsavelHtml = $responsavelRows !== '' ? '<h4>Responsável</h4><table cellpadding="4" border="1">' . $responsavelRows . '</table>' : '';

$termosContrato = trim((string)($dados['TermosContrato'] ?? ''));
$termosHtml     = $termosContrato !== '' ? html_entity_decode($termosContrato) : '';

$htmlConteudo = '
<h2 style="text-align:center;"><b>TERMO DE RESPONSABILIDADE E COMPROMISSO</b></h2>
<h4>Dados do Beneficiário</h4>
<table cellpadding="4" border="1">
    <tr><td><b>Nome</b></td><td>'             . htmlspecialchars((string)($dados['Nome']       ?? '')) . '</td></tr>
    <tr><td><b>Data de nascimento</b></td><td>' . htmlspecialchars((string)($dados['Nascimento'] ?? '')) . '</td></tr>
    <tr><td><b>CPF</b></td><td>'               . htmlspecialchars((string)($dados['CPF']        ?? '')) . '</td></tr>
    <tr><td><b>Endereço</b></td><td>'          . htmlspecialchars((string)($dados['Endereco']   ?? '')) . '</td></tr>
    <tr><td><b>Bairro</b></td><td>'            . htmlspecialchars((string)($dados['Bairro']     ?? '')) . '</td></tr>
    <tr><td><b>Cidade/UF</b></td><td>'         . htmlspecialchars(trim(($dados['Cidade'] ?? '') . ' / ' . ($dados['UF'] ?? ''))) . '</td></tr>
    <tr><td><b>Telefone</b></td><td>'          . htmlspecialchars((string)($dados['Telefone']   ?? '')) . '</td></tr>
    <tr><td><b>WhatsApp</b></td><td>'          . htmlspecialchars((string)($dados['WhatsApp']   ?? '')) . '</td></tr>
</table>
' . $responsavelHtml . $termosHtml;

// Gera PDF em memória com LoteContratoPDF (mesmo layout que gerarContrato.php)
$logoProjetoPath = loteContratoResolveProjectLogoPath((string)($dados['LogoProjeto'] ?? ''), $BASE_para_PATH);

$pdf = new LoteContratoPDF();
$pdf->logoPath        = $logoProjetoPath;
$pdf->logoSistemaPath = (string)($configAssinatura['logoSistemaPath'] ?? '');
$pdf->SetCreator('Conecta OSC');
$pdf->SetAuthor('Conecta OSC');
$pdf->SetTitle('Preview - ' . $nome);
$pdf->SetMargins(15, 40, 15);
$pdf->SetHeaderMargin(0);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 11);

$pdf->writeHTML($htmlConteudo, true, false, true, false, '');

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

// URL de destino após confirmar posição
$confirmarBase = rtrim((string)$BASE_para_URL, '/') . '/matriculas/contrato'
    . '?turma=' . $idTurma
    . '&aluno=' . $idUsuario;

$nomeExibicao = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Posicionar assinatura — <?= $nomeExibicao ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';</script>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", Arial, sans-serif; background: #f1f5f9; color: #0f172a; }
        .layout { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px; flex-shrink: 0; background: #fff; border-right: 1px solid #e2e8f0;
            padding: 28px 20px; display: flex; flex-direction: column; gap: 16px;
        }
        .sidebar h2 { margin: 0; font-size: 1.05rem; color: #0f172a; }
        .sidebar p  { margin: 0; font-size: 0.87rem; color: #475569; line-height: 1.5; }
        .coords-box {
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;
            padding: 10px 12px; font-size: 0.82rem; color: #334155; font-family: monospace;
        }
        #btnConfirmar {
            margin-top: auto; padding: 13px; background: #2563eb; color: #fff;
            border: none; border-radius: 10px; font-size: 0.95rem; font-weight: 600;
            cursor: pointer; width: 100%;
        }
        #btnConfirmar:hover { background: #1d4ed8; }
        .viewer-area {
            flex: 1; overflow-y: auto; padding: 24px; display: flex;
            flex-direction: column; align-items: center; gap: 16px;
        }
        .page-wrapper { position: relative; background: white; box-shadow: 0 2px 12px rgba(0,0,0,.15); }
        .page-wrapper canvas { display: block; }
        #sigOverlay {
            position: absolute; cursor: grab; user-select: none;
            background: rgba(255,255,255,0.92); border: 2px dashed #2563eb;
            border-radius: 8px; padding: 7px 10px;
            display: flex; flex-direction: column; gap: 3px;
            min-width: 180px; touch-action: none; z-index: 10;
        }
        #sigOverlay.dragging { cursor: grabbing; opacity: 0.85; }
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
        .loading-msg { color: #64748b; font-size: 0.9rem; padding: 2rem; }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <h2>Posicionar assinatura digital</h2>
        <p>Beneficiário: <strong><?= $nomeExibicao ?></strong></p>
        <p>Arraste ou clique na página do contrato para posicionar o bloco de assinatura digital no local desejado.</p>
        <div class="coords-box" id="coordsDisp">X: — mm  |  Y: — mm</div>
        <button id="btnConfirmar">Confirmar e gerar contrato</button>
    </aside>

    <div class="viewer-area" id="viewerArea">
        <p class="loading-msg" id="loadingMsg">Carregando prévia do contrato…</p>
    </div>
</div>

<div id="sigOverlay" style="display:none;">
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
const PDF_BASE64    = <?= json_encode($pdfBase64) ?>;
const TOTAL_PAGES   = <?= (int)$totalPages ?>;
const PAGINA_ESPACO = <?= (int)$paginaComEspaco ?>;
const Y_SPACER_MM   = <?= json_encode($ySignaturaStart) ?>;
const CONFIRMAR_BASE = <?= json_encode($confirmarBase) ?>;

const ptPerMm = 72 / 25.4;
let RENDER_SCALE    = 1.2;
let lastPageViewport = null;
let lastPageWrapper  = null;
let isDragging = false;
let dragOffX = 0, dragOffY = 0;

const viewerArea = document.getElementById('viewerArea');
const loadingMsg = document.getElementById('loadingMsg');
const overlay    = document.getElementById('sigOverlay');
const coordsDisp = document.getElementById('coordsDisp');

overlay.addEventListener('mousedown', startDrag);
overlay.addEventListener('touchstart', startDrag, { passive: false });
function startDrag(e) {
    e.preventDefault();
    isDragging = true;
    overlay.classList.add('dragging');
    const rect = overlay.getBoundingClientRect();
    const cx = (e.touches ? e.touches[0] : e).clientX;
    const cy = (e.touches ? e.touches[0] : e).clientY;
    dragOffX = cx - rect.left;
    dragOffY = cy - rect.top;
}
document.addEventListener('mousemove', onDrag);
document.addEventListener('touchmove', onDrag, { passive: false });
function onDrag(e) {
    if (!isDragging || !lastPageWrapper) return;
    e.preventDefault();
    const cx = (e.touches ? e.touches[0] : e).clientX;
    const cy = (e.touches ? e.touches[0] : e).clientY;
    const wr = lastPageWrapper.getBoundingClientRect();
    moveTo(cx - wr.left - dragOffX, cy - wr.top - dragOffY);
}
document.addEventListener('mouseup',  () => { isDragging = false; overlay.classList.remove('dragging'); });
document.addEventListener('touchend', () => { isDragging = false; overlay.classList.remove('dragging'); });

function bindPageClick(canvas, wrapper) {
    canvas.addEventListener('click', function (e) {
        if (isDragging) return;
        const wr = wrapper.getBoundingClientRect();
        moveTo(e.clientX - wr.left - overlay.offsetWidth / 2,
               e.clientY - wr.top  - overlay.offsetHeight / 2);
    });
}

function moveTo(left, top) {
    if (!lastPageWrapper) return;
    const l = Math.max(0, Math.min(lastPageWrapper.offsetWidth  - overlay.offsetWidth,  left));
    const t = Math.max(0, Math.min(lastPageWrapper.offsetHeight - overlay.offsetHeight, top));
    overlay.style.left = l + 'px';
    overlay.style.top  = t + 'px';
    const mm = pxToMm(l, t);
    coordsDisp.textContent = 'X: ' + mm.x.toFixed(1) + ' mm  |  Y: ' + mm.y.toFixed(1) + ' mm';
}

function pxToMm(leftPx, topPx) {
    return { x: leftPx / (RENDER_SCALE * ptPerMm), y: topPx / (RENDER_SCALE * ptPerMm) };
}

function setDefaultPosition() {
    if (!lastPageViewport || !lastPageWrapper) return;
    const pageWidthMm  = lastPageViewport.width / (ptPerMm * RENDER_SCALE);
    const groupWidthMm = 119.0;
    const defaultLeft  = Math.max(0, (pageWidthMm - groupWidthMm) / 2 * ptPerMm * RENDER_SCALE);
    let defaultTop;
    if (Y_SPACER_MM !== null) {
        // +7 px compensa o padding-top do overlay para que o visual
        // do bloco coincida com a posição real do QR no PDF.
        defaultTop = (Y_SPACER_MM + 2.5) * ptPerMm * RENDER_SCALE + 7;
    } else {
        defaultTop = lastPageViewport.height * 0.68;
    }
    moveTo(defaultLeft, defaultTop);
}

async function renderPdf() {
    const raw   = atob(PDF_BASE64);
    const bytes = new Uint8Array(raw.length);
    for (let i = 0; i < raw.length; i++) bytes[i] = raw.charCodeAt(i);

    const pdfDoc = await pdfjsLib.getDocument({ data: bytes }).promise;
    loadingMsg.remove();

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

document.getElementById('btnConfirmar').addEventListener('click', function () {
    const leftPx = parseFloat(overlay.style.left || '0');
    const topPx  = parseFloat(overlay.style.top  || '0');
    // Subtrai o padding-top do overlay (7 px) para que a posição real do QR
    // no PDF corresponda ao que o usuário vê dentro do bloco de preview.
    const OVERLAY_PAD_TOP_PX = 7;
    const mm = pxToMm(leftPx, Math.max(0, topPx - OVERLAY_PAD_TOP_PX));
    window.location.href = CONFIRMAR_BASE + '&sig_x=' + mm.x.toFixed(3) + '&sig_y=' + mm.y.toFixed(3);
});

renderPdf().catch(err => {
    loadingMsg.textContent = 'Erro ao carregar prévia: ' + (err.message || err);
    if (!loadingMsg.parentNode) viewerArea.appendChild(loadingMsg);
});
</script>
</body>
</html>
