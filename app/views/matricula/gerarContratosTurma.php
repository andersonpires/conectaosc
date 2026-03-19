<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];
$appJsVersion = @filemtime($BASE_para_PATH . '/app/assets/js/app.js') ?: time();

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

$idTurma = isset($_GET['turma']) ? (int)$_GET['turma'] : 0;
if ($idTurma <= 0) {
    die("<div class='alert alert-danger'>Parâmetros inválidos.</div>");
}

$sqlTurma = $pdo->prepare('SELECT t.IdTurma, t.NomeTurma, c.NomeCurso FROM tbTurma t JOIN tbCurso c ON c.IdCurso = t.IdCurso WHERE t.IdTurma = ? LIMIT 1');
$sqlTurma->execute([$idTurma]);
$turma = $sqlTurma->fetch(PDO::FETCH_ASSOC);
if (!$turma) {
    die("<div class='alert alert-danger'>Turma não encontrada.</div>");
}

$sqlLista = $pdo->prepare("\n    SELECT m.IdMatricula, a.Nome\n      FROM tbMatricula m\n      JOIN tbAluno a ON a.IdUsuario = m.IdUsuario\n     WHERE m.IdTurma = ?\n       AND m.Habilitado = 1\n       AND a.Habilitado = 1\n  ORDER BY a.Nome ASC\n");
$sqlLista->execute([$idTurma]);
$lista = $sqlLista->fetchAll(PDO::FETCH_ASSOC) ?: [];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <style>
        .status-processando {
            color: #0d6efd;
            font-weight: 600;
        }

        .status-concluido {
            color: #198754;
            font-weight: 600;
        }

        .status-erro {
            color: #dc3545;
            font-weight: 600;
        }

        .spinner-inline {
            width: 1rem;
            height: 1rem;
            border-width: 0.14em;
            margin-right: 0.35rem;
            vertical-align: text-bottom;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>

        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>
            <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js?v=<?php echo $appJsVersion; ?>"></script>

            <main class="content">
                <div class="container mt-4">
                    <h2>Gerar contratos da turma</h2>
                    <p class="mb-1">Curso: <b><?= htmlspecialchars((string)$turma['NomeCurso'], ENT_QUOTES, 'UTF-8') ?></b></p>
                    <p class="mb-4">Turma: <b><?= htmlspecialchars((string)$turma['NomeTurma'], ENT_QUOTES, 'UTF-8') ?></b></p>

                    <?php if (empty($lista)) { ?>
                        <div class="alert alert-warning">Nenhum aluno ativo encontrado nesta turma.</div>
                    <?php } else { ?>
                        <div class="d-flex gap-2 mb-3">
                            <button id="btnIniciar" class="btn btn-primary">Iniciar geração sequencial</button>
                            <span id="statusGlobal" class="align-self-center text-muted"></span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Beneficiário</th>
                                        <th>Situação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lista as $idx => $item) { ?>
                                        <tr id="row-<?= (int)$item['IdMatricula'] ?>">
                                            <td><?= $idx + 1 ?></td>
                                            <td><?= htmlspecialchars((string)$item['Nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td id="status-<?= (int)$item['IdMatricula'] ?>">Ativo</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } ?>
                </div>
            </main>

            <footer class="footer">
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>
        </div>
    </div>

    <?php if (!empty($lista)) { ?>
        <script>
            const contratos = <?= json_encode(array_map(static function ($item) {
                                return [
                                    'id_matricula' => (int)$item['IdMatricula'],
                                    'nome' => (string)$item['Nome'],
                                ];
                            }, $lista), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

            const processUrl = "<?= rtrim((string)$BASE_para_URL, '/') ?>/matriculas/contratos/turma/processar";
            const statusGlobal = document.getElementById('statusGlobal');
            const btnIniciar = document.getElementById('btnIniciar');

            function setStatus(idMatricula, html, cssClass = '') {
                const el = document.getElementById(`status-${idMatricula}`);
                if (!el) return;
                el.className = cssClass;
                el.innerHTML = html;
            }

            function triggerDownload(url) {
                const a = document.createElement('a');
                a.href = url;
                a.target = '_blank';
                a.rel = 'noopener noreferrer';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }

            async function processarItem(item, indice, total) {
                setStatus(item.id_matricula, '<span class="spinner-border spinner-border-sm spinner-inline" role="status" aria-hidden="true"></span>Processando...', 'status-processando');
                statusGlobal.textContent = `Processando ${indice + 1} de ${total}...`;

                const body = new URLSearchParams();
                body.set('id_matricula', String(item.id_matricula));

                const resp = await fetch(processUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: body.toString()
                });

                const data = await resp.json();
                if (!resp.ok || !data.ok) {
                    const erro = data && data.erro ? data.erro : `Falha HTTP ${resp.status}`;
                    setStatus(item.id_matricula, `Erro: ${erro}`, 'status-erro');
                    return false;
                }

                setStatus(item.id_matricula, 'Concluído', 'status-concluido');
                if (data.url_download) {
                    triggerDownload(data.url_download);
                }
                return true;
            }

            btnIniciar.addEventListener('click', async () => {
                btnIniciar.disabled = true;
                let sucesso = 0;

                for (let i = 0; i < contratos.length; i++) {
                    const ok = await processarItem(contratos[i], i, contratos.length);
                    if (ok) {
                        sucesso++;
                    }
                }

                statusGlobal.textContent = `Finalizado: ${sucesso}/${contratos.length} contrato(s) gerado(s).`;
                btnIniciar.disabled = false;
            });
        </script>
    <?php } ?>
</body>

</html>
