<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('PHPSESSID3');
    session_set_cookie_params(['httponly' => true]);
    session_start();
    session_regenerate_id(true);
}

date_default_timezone_set('America/Sao_Paulo');
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$BASE_para_PATH = (string)($runtime['base_para_path'] ?? '');
$BASE_para_URL = (string)($runtime['base_para_url'] ?? '');

if ($BASE_para_PATH === '' || $BASE_para_URL === '') {
    $redirectUrl = urlencode((string) ($_SERVER['REQUEST_URI'] ?? '/'));
    $fallbackBaseUrl = '/' . basename(dirname(__DIR__, 2));
    $baseUrl = rtrim((string) ($BASE_para_URL !== '' ? $BASE_para_URL : $fallbackBaseUrl), '/');
    header('Location: ' . $baseUrl . '/login/?redirect=' . $redirectUrl);
    exit();
}

require_once $BASE_para_PATH . '/api/legacy/checa-token.php';
require_once $BASE_para_PATH . '/api/conectabd/conexao.php';

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS tbBackupLog (
        IdLog INT(11) NOT NULL AUTO_INCREMENT,
        DataHora DATETIME NOT NULL,
        Tipo VARCHAR(30) NOT NULL DEFAULT 'backup',
        Etapa VARCHAR(80) NOT NULL,
        Status VARCHAR(20) NOT NULL,
        Mensagem TEXT NOT NULL,
        PRIMARY KEY (IdLog),
        KEY idx_backup_log_datahora (DataHora)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"
);

$sql = $pdo->query('SELECT * FROM tbbackupconfig LIMIT 1');
$backupConfig = $sql ? $sql->fetch(PDO::FETCH_ASSOC) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $backupConfig) {
    $intervalo = (int) ($_POST['intervalo'] ?? 24);
    $email = trim((string) ($_POST['email'] ?? ''));

    $stmt = $pdo->prepare('UPDATE tbbackupconfig SET IntervaloHoras = ?, EmailDestino = ? WHERE IdConfig = ?');
    $stmt->execute([$intervalo, $email, $backupConfig['IdConfig']]);

    header('Location: ' . rtrim((string) $BASE_para_URL, '/') . '/backup/?msg=' . urlencode('Configurações salvas com sucesso!'));
    exit();
}

$backupLogs = $pdo->query('SELECT DataHora, Etapa, Status, Mensagem FROM tbBackupLog ORDER BY DataHora DESC, IdLog DESC LIMIT 20')->fetchAll(PDO::FETCH_ASSOC);
$reduzFotoLogs = $pdo->query('SELECT DataHora, QtdEncontrada, Economia, Resultado FROM tbReduzfoto ORDER BY DataHora DESC, IdAtividade DESC LIMIT 20')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <style>
        .card-config {
            max-width: 650px;
            margin: 0 auto;
        }

        .logs-section {
            margin-top: 2rem;
        }

        .logs-table td,
        .logs-table th {
            vertical-align: top;
            font-size: 0.9rem;
        }
    </style>
</head>

<script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js"></script>

<body>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>

        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>

            <main class="content">
                <div class="container-fluid p-0">
                    <h1 class="h3 mb-3">Configuração do Backup Automático</h1>

                    <?php if (isset($_GET['msg'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars((string) $_GET['msg'], ENT_QUOTES, 'UTF-8'); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card card-config">
                        <div class="card-header">
                            <h5 class="card-title">Ajustar Intervalo e Destino</h5>
                            <h6 class="card-subtitle text-muted">O sistema enviará automaticamente um backup a cada intervalo configurado.</h6>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="mb-3">
                                    <label class="form-label">Intervalo de Backup (em horas):</label>
                                    <select class="form-select" name="intervalo" required>
                                        <?php for ($i = 1; $i <= 24; $i++): ?>
                                            <option value="<?= $i ?>" <?= (int) ($backupConfig['IntervaloHoras'] ?? 24) === $i ? 'selected' : '' ?>>
                                                <?= $i ?> hora(s)
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">E-mail para envio do backup:</label>
                                    <input
                                        type="email"
                                        class="form-control"
                                        name="email"
                                        required
                                        value="<?= htmlspecialchars((string) ($backupConfig['EmailDestino'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <button type="submit" class="btn btn-primary w-100">
                                    Salvar configurações
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="logs-section">
                        <h2 class="h4 mb-3">Registros dos últimos logs de backup e redução de fotos</h2>
                        <div class="row g-3">
                            <div class="col-12 col-xl-6">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Logs de Backup</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover logs-table">
                                                <thead>
                                                    <tr>
                                                        <th>Data/Hora</th>
                                                        <th>Etapa</th>
                                                        <th>Status</th>
                                                        <th>Mensagem</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if ($backupLogs): ?>
                                                        <?php foreach ($backupLogs as $log): ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars((string) ($log['DataHora'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td><?= htmlspecialchars((string) ($log['Etapa'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td><?= htmlspecialchars((string) ($log['Status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td><?= htmlspecialchars((string) ($log['Mensagem'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="4">Nenhum log de backup encontrado.</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-xl-6">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Logs de Redução de Fotos</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover logs-table">
                                                <thead>
                                                    <tr>
                                                        <th>Data/Hora</th>
                                                        <th>Qtd.</th>
                                                        <th>Economia (KB)</th>
                                                        <th>Resultado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if ($reduzFotoLogs): ?>
                                                        <?php foreach ($reduzFotoLogs as $log): ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars((string) ($log['DataHora'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td><?= htmlspecialchars((string) ($log['QtdEncontrada'] ?? 0), ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td><?= htmlspecialchars(number_format((float) ($log['Economia'] ?? 0), 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td><?= htmlspecialchars((string) ($log['Resultado'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="4">Nenhum log de reducao de fotos encontrado.</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="footer">
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php'; ?>
            </footer>
        </div>
    </div>
</body>
</html>
