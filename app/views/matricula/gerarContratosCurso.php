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

function contratosCursoStatusLabel(int $status): string
{
    if ($status === 0) {
        return 'Concluído';
    }
    if ($status === 1) {
        return 'Pendente';
    }
    if ($status === 2) {
        return 'Processando';
    }
    if ($status === 9) {
        return 'Erro';
    }

    return 'Desconhecido';
}

function contratosCursoZipPublicUrl(string $baseUrl, string $zipName, string $storedUrl = ''): string
{
    $zipName = trim($zipName);
    if ($zipName !== '') {
        return rtrim($baseUrl, '/') . '/app/storage/assinatura/lotes/' . rawurlencode($zipName);
    }

    $storedUrl = trim($storedUrl);
    if ($storedUrl === '') {
        return '';
    }

    $path = (string) (parse_url($storedUrl, PHP_URL_PATH) ?? '');
    if ($path === '') {
        return '';
    }

    $zipBaseName = basename($path);
    if ($zipBaseName === '' || $zipBaseName === '.' || $zipBaseName === '..') {
        return '';
    }

    return rtrim($baseUrl, '/') . '/app/storage/assinatura/lotes/' . rawurlencode($zipBaseName);
}

$idCurso = isset($_REQUEST['curso']) ? (int) $_REQUEST['curso'] : 0;
if ($idCurso <= 0) {
    die("<div class='alert alert-danger'>Parâmetros inválidos.</div>");
}

$sqlCurso = $pdo->prepare('SELECT IdCurso, NomeCurso FROM tbCurso WHERE IdCurso = ? LIMIT 1');
$sqlCurso->execute([$idCurso]);
$curso = $sqlCurso->fetch(PDO::FETCH_ASSOC);
if (!$curso) {
    die("<div class='alert alert-danger'>Curso não encontrado.</div>");
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'status') {
    header('Content-Type: application/json; charset=UTF-8');

    try {
        $stmtHistAjax = $pdo->prepare("
            SELECT IdCronContrato, Status, NomeZip, UrlZip, MensagemErro, DataSolicitacao, DataAtualizacao, EmailDestino
              FROM tb_Cron_Contrato
             WHERE TipoReferencia = 'CURSO'
               AND IdCurso = ?
          ORDER BY IdCronContrato DESC
             LIMIT 10
        ");
        $stmtHistAjax->execute([$idCurso]);
        $historicoAjax = $stmtHistAjax->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $items = array_map(static function (array $row) use ($BASE_para_URL): array {
            $status = (int) ($row['Status'] ?? -1);

            return [
                'id' => (int) ($row['IdCronContrato'] ?? 0),
                'status' => $status,
                'statusLabel' => contratosCursoStatusLabel($status),
                'dataSolicitacao' => (string) ($row['DataSolicitacao'] ?? ''),
                'dataAtualizacao' => (string) ($row['DataAtualizacao'] ?? ''),
                'emailDestino' => (string) ($row['EmailDestino'] ?? ''),
                'mensagemErro' => (string) ($row['MensagemErro'] ?? ''),
                'urlZip' => contratosCursoZipPublicUrl(
                    (string) $BASE_para_URL,
                    (string) ($row['NomeZip'] ?? ''),
                    (string) ($row['UrlZip'] ?? '')
                ),
            ];
        }, $historicoAjax);

        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['erro' => 'Falha ao consultar o status dos lotes.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    exit;
}

$idSolicitante = (int) ($_SESSION['Cod'] ?? 0);
$sqlUser = $pdo->prepare('SELECT Email FROM tbUser WHERE IdColaborador = ? LIMIT 1');
$sqlUser->execute([$idSolicitante]);
$user = $sqlUser->fetch(PDO::FETCH_ASSOC) ?: [];

$emailPadrao = trim((string) ($user['Email'] ?? ''));
$emailDestino = $emailPadrao;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailDestino = trim((string) ($_POST['email_destino'] ?? ''));

    if ($emailDestino === '' || !filter_var($emailDestino, FILTER_VALIDATE_EMAIL)) {
        $target = rtrim((string) $BASE_para_URL, '/') . '/matriculas/contratos/curso?curso=' . $idCurso . '&erro=' . urlencode('Informe um e-mail válido para receber os contratos.');
        header('Location: ' . $target);
        exit;
    }

    try {
        $tokenDownload = bin2hex(random_bytes(20));
        $stmt = $pdo->prepare("
            INSERT INTO tb_Cron_Contrato
                (TipoReferencia, IdCurso, NomeCurso, NomeTurma, EmailDestino, Status, IdColaboradorSolicitante, TokenDownload, DataSolicitacao, DataAtualizacao)
            VALUES
                ('CURSO', ?, ?, ?, ?, 1, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $idCurso,
            (string) $curso['NomeCurso'],
            'Todas as turmas',
            $emailDestino,
            $idSolicitante > 0 ? $idSolicitante : null,
            $tokenDownload,
        ]);

        $target = rtrim((string) $BASE_para_URL, '/') . '/matriculas/contratos/curso?curso=' . $idCurso . '&msg=' . urlencode('Solicitação registrada com sucesso. O processamento será realizado pelo cron e o envio será feito para ' . $emailDestino . '.');
        header('Location: ' . $target);
        exit;
    } catch (Throwable $e) {
        $target = rtrim((string) $BASE_para_URL, '/') . '/matriculas/contratos/curso?curso=' . $idCurso . '&erro=' . urlencode('Erro ao registrar solicitação: ' . $e->getMessage());
        header('Location: ' . $target);
        exit;
    }
}

$historico = [];
try {
    $stmtHist = $pdo->prepare("
        SELECT IdCronContrato, Status, NomeZip, UrlZip, MensagemErro, DataSolicitacao, DataAtualizacao, EmailDestino
          FROM tb_Cron_Contrato
         WHERE TipoReferencia = 'CURSO'
           AND IdCurso = ?
      ORDER BY IdCronContrato DESC
         LIMIT 10
    ");
    $stmtHist->execute([$idCurso]);
    $historico = $stmtHist->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $historico = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <style>
        .contrato-zip-loading {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
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
                    <h2>Gerar contratos do curso</h2>
                    <p class="mb-4">Curso: <b><?= htmlspecialchars((string) $curso['NomeCurso'], ENT_QUOTES, 'UTF-8') ?></b></p>

                    <?php if (isset($_GET['msg']) && trim((string) $_GET['msg']) !== '') { ?>
                        <div class="alert alert-success"><?= htmlspecialchars(trim((string) $_GET['msg']), ENT_QUOTES, 'UTF-8') ?></div>
                    <?php } ?>

                    <?php if (isset($_GET['erro']) && trim((string) $_GET['erro']) !== '') { ?>
                        <div class="alert alert-danger"><?= htmlspecialchars(trim((string) $_GET['erro']), ENT_QUOTES, 'UTF-8') ?></div>
                    <?php } ?>

                    <div class="card mb-4">
                        <div class="card-body">
                            <p class="mb-3">Todos os contratos gerados serão enviados por e-mail após o processamento. Confirme o e-mail de recebimento:</p>

                            <form method="POST" class="row g-3">
                                <input type="hidden" name="curso" value="<?= $idCurso ?>">

                                <div class="col-12 col-md-8">
                                    <label for="email_destino" class="form-label">E-mail para recebimento</label>
                                    <input type="email" id="email_destino" name="email_destino" class="form-control" required value="<?= htmlspecialchars($emailDestino, ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="col-12 col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">Solicitar geração em lote</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Últimas solicitações deste curso</h5>

                            <?php if (empty($historico)) { ?>
                                <div class="alert alert-light border mb-0">Nenhuma solicitação registrada para este curso.</div>
                            <?php } else { ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Status</th>
                                                <th>Data da solicitação</th>
                                                <th>Última atualização</th>
                                                <th>E-mail</th>
                                                <th>Arquivo ZIP</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($historico as $row) {
                                                $status = (int) ($row['Status'] ?? -1);
                                                $urlZip = contratosCursoZipPublicUrl((string) $BASE_para_URL, (string) ($row['NomeZip'] ?? ''), (string) ($row['UrlZip'] ?? ''));
                                            ?>
                                                <tr data-job-id="<?= (int) $row['IdCronContrato'] ?>">
                                                    <td><?= (int) $row['IdCronContrato'] ?></td>
                                                    <td class="js-status"><?= htmlspecialchars(contratosCursoStatusLabel($status), ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td><?= htmlspecialchars((string) $row['DataSolicitacao'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td class="js-atualizacao"><?= htmlspecialchars((string) $row['DataAtualizacao'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td><?= htmlspecialchars((string) $row['EmailDestino'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td class="js-zip-cell">
                                                        <?php if ($status === 0 && $urlZip !== '') { ?>
                                                            <a href="<?= htmlspecialchars($urlZip, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Baixar ZIP</a>
                                                        <?php } elseif ($status === 9) { ?>
                                                            <span class="text-danger"><?= htmlspecialchars((string) ($row['MensagemErro'] ?? 'Falha ao processar.'), ENT_QUOTES, 'UTF-8') ?></span>
                                                        <?php } elseif ($status === 1 || $status === 2) { ?>
                                                            <span class="contrato-zip-loading">
                                                                <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                                                <span>Aguardando arquivo</span>
                                                            </span>
                                                        <?php } else { ?>
                                                            <span>-</span>
                                                        <?php } ?>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="footer">
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>
        </div>
    </div>
    <script>
        (function() {
            const endpoint = <?php echo json_encode(rtrim((string) $BASE_para_URL, '/') . '/matriculas/contratos/curso?curso=' . $idCurso . '&ajax=status', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            let pollingHandle = null;

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function renderZipCell(item) {
                if (item.status === 0 && item.urlZip) {
                    return `<a href="${escapeHtml(item.urlZip)}" target="_blank" rel="noopener noreferrer">Baixar ZIP</a>`;
                }

                if (item.status === 9) {
                    return `<span class="text-danger">${escapeHtml(item.mensagemErro || 'Falha ao processar.')}</span>`;
                }

                if (item.status === 1 || item.status === 2) {
                    return `
                        <span class="contrato-zip-loading">
                            <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                            <span>Aguardando arquivo</span>
                        </span>
                    `;
                }

                return '<span>-</span>';
            }

            function updateTable(items) {
                let hasPending = false;

                items.forEach((item) => {
                    const row = document.querySelector(`tr[data-job-id="${item.id}"]`);
                    if (!row) {
                        return;
                    }

                    const statusCell = row.querySelector('.js-status');
                    const atualizacaoCell = row.querySelector('.js-atualizacao');
                    const zipCell = row.querySelector('.js-zip-cell');

                    if (statusCell) {
                        statusCell.textContent = item.statusLabel || '';
                    }
                    if (atualizacaoCell) {
                        atualizacaoCell.textContent = item.dataAtualizacao || '';
                    }
                    if (zipCell) {
                        zipCell.innerHTML = renderZipCell(item);
                    }

                    if (item.status === 1 || item.status === 2) {
                        hasPending = true;
                    }
                });

                if (!hasPending && pollingHandle) {
                    window.clearInterval(pollingHandle);
                    pollingHandle = null;
                }
            }

            async function pollStatus() {
                try {
                    const response = await fetch(endpoint, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        cache: 'no-store'
                    });

                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();
                    if (!data || !Array.isArray(data.items)) {
                        return;
                    }

                    updateTable(data.items);
                } catch (error) {
                    console.error('Erro ao atualizar o status dos lotes de contrato.', error);
                }
            }

            if (document.querySelector('tr[data-job-id]')) {
                pollStatus();
                pollingHandle = window.setInterval(pollStatus, 30000);
            }
        })();
    </script>
</body>

</html>
