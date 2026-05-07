<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', '86400');
}

if (!isset($BASE_para_PATH) || !isset($BASE_para_URL)) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI'] ?? '');
    header('Location: ' . rtrim((string)($BASE_para_URL ?? ''), '/') . '/login/?redirect=' . $redirect_url);
    exit();
}

require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';

$idCurso = isset($_GET['curso']) ? (int)$_GET['curso'] : 0;
if ($idCurso <= 0) {
    http_response_code(400);
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Erro</title></head><body><p style="color:red;font-family:sans-serif;padding:2rem;">Parâmetro de curso inválido.</p></body></html>';
    exit;
}

$modo = (isset($_GET['modo']) && $_GET['modo'] === 'zip') ? 'zip' : 'individual';
$assinar = isset($_GET['assinar']) && (string)$_GET['assinar'] === '1' ? 1 : 0;
$sigX    = isset($_GET['sig_x'])     && is_numeric($_GET['sig_x'])     ? (float)$_GET['sig_x']     : null;
$sigYRel = isset($_GET['sig_y_rel']) && is_numeric($_GET['sig_y_rel']) ? (float)$_GET['sig_y_rel'] : null;

$idTurma = isset($_GET['turma']) ? (int)$_GET['turma'] : 0;
$nomeTurma = '';
if ($idTurma > 0) {
    $stmtTurma = $pdo->prepare('SELECT NomeTurma FROM tbTurma WHERE IdTurma = ? AND Habilitado = 1 LIMIT 1');
    $stmtTurma->execute([$idTurma]);
    $nomeTurma = (string)($stmtTurma->fetchColumn() ?: '');
    if ($nomeTurma === '') {
        $idTurma = 0;
    }
}

$sqlCurso = $pdo->prepare('SELECT IdCurso, NomeCurso FROM tbCurso WHERE IdCurso = ? AND Habilitado = 1 LIMIT 1');
$sqlCurso->execute([$idCurso]);
$curso = $sqlCurso->fetch(PDO::FETCH_ASSOC);
if (!$curso) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Erro</title></head><body><p style="color:red;font-family:sans-serif;padding:2rem;">Curso não encontrado.</p></body></html>';
    exit;
}

if ($idTurma > 0) {
    $stmtLista = $pdo->prepare("
        SELECT m.IdMatricula, a.Nome
          FROM tbMatricula m
          JOIN (
                SELECT MAX(IdMatricula) AS IdMatricula
                  FROM tbMatricula
                 WHERE IdCurso = ?
                   AND IdTurma = ?
                   AND Habilitado = 1
                 GROUP BY IdUsuario
               ) ult ON ult.IdMatricula = m.IdMatricula
          JOIN tbAluno a ON m.IdUsuario = a.IdUsuario
          JOIN tbCurso c ON m.IdCurso = c.IdCurso
          JOIN tbTurma t ON m.IdTurma = t.IdTurma
         WHERE c.IdCurso = ?
           AND c.Habilitado = 1
           AND t.IdTurma = ?
           AND t.Habilitado = 1
           AND a.Habilitado = 1
           AND m.Habilitado = 1
      ORDER BY a.Nome
    ");
    $stmtLista->execute([$idCurso, $idTurma, $idCurso, $idTurma]);
} else {
    $stmtLista = $pdo->prepare("
        SELECT m.IdMatricula, a.Nome
          FROM tbMatricula m
          JOIN (
                SELECT MAX(IdMatricula) AS IdMatricula
                  FROM tbMatricula
                 WHERE IdCurso = ?
                   AND Habilitado = 1
                 GROUP BY IdUsuario
               ) ult ON ult.IdMatricula = m.IdMatricula
          JOIN tbAluno a ON m.IdUsuario = a.IdUsuario
          JOIN tbCurso c ON m.IdCurso = c.IdCurso
          JOIN tbTurma t ON m.IdTurma = t.IdTurma
         WHERE c.IdCurso = ?
           AND c.Habilitado = 1
           AND t.Habilitado = 1
           AND a.Habilitado = 1
           AND m.Habilitado = 1
      ORDER BY t.NomeTurma, a.Nome
    ");
    $stmtLista->execute([$idCurso, $idCurso]);
}
$lista = $stmtLista->fetchAll(PDO::FETCH_ASSOC) ?: [];

$processUrl = rtrim((string)$BASE_para_URL, '/') . '/matriculas/contratos/turma/processar';
$zipUrl     = rtrim((string)$BASE_para_URL, '/') . '/matriculas/contratos/curso/zip';

$itens = array_map(static fn(array $row): array => [
    'id_matricula' => (int)$row['IdMatricula'],
    'nome'         => (string)$row['Nome'],
], $lista);

$configJson = json_encode([
    'modo'       => $modo,
    'assinar'    => $assinar,
    'processUrl' => $processUrl,
    'zipUrl'     => $zipUrl,
    'itens'      => $itens,
    'sigX'    => $sigX,
    'sigYRel' => $sigYRel,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

$nomeCurso    = htmlspecialchars((string)$curso['NomeCurso'], ENT_QUOTES, 'UTF-8');
$nomeContexto = $idTurma > 0
    ? htmlspecialchars($nomeTurma, ENT_QUOTES, 'UTF-8')
    : $nomeCurso;
$labelContexto = $idTurma > 0 ? 'Turma' : 'Curso';
$modoLabel    = $modo === 'zip' ? 'ZIP com todos os contratos' : 'um PDF por aluno';
$assinarLabel = $assinar ? 'com assinatura digital' : 'sem assinatura digital';
$subtitulo    = $modo === 'zip'
    ? 'Os contratos serão gerados ' . $assinarLabel . ' e compactados em um único arquivo ZIP para download ao final.'
    : 'Os contratos serão gerados ' . $assinarLabel . '. O download de cada PDF inicia automaticamente quando chegar a 100%.';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gerando contratos — <?= $nomeCurso ?></title>
    <style>
        :root { color-scheme: light; }
        body { margin: 0; font-family: "Segoe UI", Arial, sans-serif; background: #f8fafc; color: #0f172a; }
        .wrap { max-width: 960px; margin: 0 auto; padding: 32px 16px 48px; }
        h1 { margin: 0 0 4px; font-size: 1.4rem; }
        .curso-nome { margin: 0 0 6px; font-size: 1rem; color: #334155; }
        .subtitulo { margin: 0 0 28px; color: #64748b; font-size: 0.9rem; }
        .list { display: grid; gap: 12px; }
        .item { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 12px 16px; }
        .item-head { display: flex; justify-content: space-between; gap: 10px; align-items: center; margin-bottom: 9px; }
        .item-name { font-size: 0.95rem; font-weight: 600; color: #0f172a; }
        .item-status { font-size: 0.83rem; color: #64748b; }
        .item-status.ok { color: #166534; }
        .item-status.error { color: #b91c1c; }
        .track { width: 100%; height: 9px; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
        .bar { width: 0%; height: 100%; background: #2563eb; border-radius: 999px; transition: width 180ms linear; }
        .bar.error { background: #dc2626; }
        .summary { margin-top: 22px; font-size: 0.92rem; color: #334155; }
        .btn-download {
            display: inline-block; margin-top: 20px; padding: 12px 28px;
            background: #2563eb; color: #fff; border-radius: 10px;
            text-decoration: none; font-weight: 600; font-size: 0.95rem;
        }
        .btn-download:hover { background: #1d4ed8; }
        .aviso-vazio { padding: 2rem; color: #92400e; background: #fffbeb; border: 1px solid #fcd34d; border-radius: 12px; }
    </style>
</head>
<body>
<main class="wrap">
    <h1>Gerando contratos</h1>
    <p class="curso-nome"><?= $labelContexto ?>: <strong><?= $nomeContexto ?></strong></p>
    <p class="subtitulo"><?= htmlspecialchars($subtitulo, ENT_QUOTES, 'UTF-8') ?></p>

    <?php if (empty($lista)): ?>
        <div class="aviso-vazio">Nenhum aluno ativo com matrícula ativa foi encontrado neste curso.</div>
    <?php else: ?>
        <section class="list" id="contrato-list"></section>
        <p class="summary" id="summary"></p>
    <?php endif; ?>
</main>

<?php if (!empty($lista)): ?>
<script>
const CONFIG = <?= $configJson ?>;

const listEl     = document.getElementById('contrato-list');
const summaryEl  = document.getElementById('summary');

function esc(v) {
    return String(v ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c] || c));
}

function nomeParaArquivo(raw) {
    return String(raw || '')
        .normalize('NFD').replace(/[̀-ͯ]/g, '')
        .replace(/[^A-Za-z\s]/g, '').replace(/\s+/g, ' ').trim().slice(0, 40).trim() || 'beneficiario';
}

function criarCard(item) {
    const el = document.createElement('article');
    el.className = 'item';
    el.innerHTML =
        '<div class="item-head">' +
            '<span class="item-name">' + esc(item.nome || ('Matrícula #' + item.id_matricula)) + '</span>' +
            '<span class="item-status">Aguardando</span>' +
        '</div>' +
        '<div class="track"><div class="bar"></div></div>';
    return {
        el,
        statusEl: el.querySelector('.item-status'),
        barEl:    el.querySelector('.bar'),
    };
}

function setStatus(card, msg, tom) {
    card.statusEl.textContent = msg;
    card.statusEl.className = 'item-status' + (tom ? ' ' + tom : '');
}

function setProgress(card, pct, tom) {
    card.barEl.style.width = Math.max(0, Math.min(100, pct)) + '%';
    card.barEl.classList.toggle('error', tom === 'error');
}

function triggerDownload(url, nome) {
    const a = document.createElement('a');
    a.href = url;
    a.download = nomeParaArquivo(nome) + '.pdf';
    a.rel = 'noopener noreferrer';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

async function processarItem(item, card) {
    let pct = 5;
    setProgress(card, pct);
    setStatus(card, 'Gerando PDF…');

    const ticker = setInterval(() => {
        pct = Math.min(pct + 3, 90);
        setProgress(card, pct);
    }, 300);

    try {
        const body = new URLSearchParams();
        body.set('id_matricula', String(item.id_matricula));
        body.set('assinar', String(CONFIG.assinar));
        if (CONFIG.sigX    !== null && CONFIG.sigX    !== undefined) body.set('sig_x',     String(CONFIG.sigX));
        if (CONFIG.sigYRel !== null && CONFIG.sigYRel !== undefined) body.set('sig_y_rel', String(CONFIG.sigYRel));

        const resp = await fetch(CONFIG.processUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body.toString(),
        });

        clearInterval(ticker);
        const data = await resp.json();

        if (!resp.ok || !data.ok) {
            throw new Error(data.erro || ('Falha HTTP ' + resp.status));
        }

        setProgress(card, 100);
        setStatus(card, 'Concluído', 'ok');

        if (CONFIG.modo === 'individual' && data.url_download) {
            triggerDownload(data.url_download, item.nome);
        }

        return { ok: true, nomeArquivo: data.nome_arquivo || '' };
    } catch (err) {
        clearInterval(ticker);
        setProgress(card, 100, 'error');
        setStatus(card, 'Erro: ' + (err.message || 'falha desconhecida'), 'error');
        return { ok: false };
    }
}

async function criarZip(nomeArquivos) {
    summaryEl.textContent = 'Compactando arquivos em ZIP…';
    try {
        const body = new URLSearchParams();
        nomeArquivos.forEach((n) => body.append('arquivos[]', n));

        const resp = await fetch(CONFIG.zipUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body.toString(),
        });
        const data = await resp.json();
        if (!resp.ok || !data.ok) throw new Error(data.erro || 'Falha ao criar ZIP.');
        return data.url_download || '';
    } catch (err) {
        summaryEl.textContent = 'Erro ao criar ZIP: ' + (err.message || 'falha desconhecida');
        return '';
    }
}

(async function run() {
    const itens = CONFIG.itens;
    if (!itens || itens.length === 0) return;

    const cards = itens.map((item) => {
        const card = criarCard(item);
        listEl.appendChild(card.el);
        return { item, card };
    });

    let sucesso = 0;
    const nomesArquivos = [];

    for (const { item, card } of cards) {
        const res = await processarItem(item, card);
        if (res.ok) {
            sucesso++;
            if (res.nomeArquivo) nomesArquivos.push(res.nomeArquivo);
        }
    }

    if (CONFIG.modo === 'zip') {
        if (nomesArquivos.length > 0) {
            const urlZip = await criarZip(nomesArquivos);
            if (urlZip) {
                summaryEl.innerHTML =
                    'Concluído: ' + sucesso + '/' + itens.length + ' contrato(s) gerado(s). ' +
                    '<a class="btn-download" href="' + esc(urlZip) + '" download>Baixar ZIP</a>';
                const a = document.createElement('a');
                a.href = urlZip;
                a.download = 'contratos.zip';
                a.rel = 'noopener noreferrer';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            } else {
                summaryEl.textContent = 'Concluído: ' + sucesso + '/' + itens.length + ' contrato(s), mas falhou ao criar o ZIP.';
            }
        } else {
            summaryEl.textContent = 'Nenhum contrato foi gerado com sucesso.';
        }
    } else {
        summaryEl.textContent = 'Concluído: ' + sucesso + '/' + itens.length + ' contrato(s) gerado(s) e enviado(s) para download.';
    }
})();
</script>
<?php endif; ?>
</body>
</html>
