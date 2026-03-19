<?php
// Início da página: inicialização de session e configuração de timezone
session_set_cookie_params(['httponly' => true]);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

// Verifica se as variáveis de sessão BASE_PATH e BASE_URL estão definidas
if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    // Salva a URL atual para redirecionar o usuário após o login
    $redirect_url = urlencode($_SERVER['REQUEST_URI']); // Codifica o endereço atual
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?redirect=$redirect_url"); // Redireciona para o login com o endereço de volta via GET
    exit(); // Garante que o código abaixo não será executado
}
?>
<?php require_once $_SESSION['BASE_PATH'] . '/checa-token.php';

// Conexão com o banco utilizando PDO
require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

// Busca configurações
$sql = $pdo->query("SELECT * FROM tbbackupconfig LIMIT 1");
$backupConfig = $sql->fetch(PDO::FETCH_ASSOC);

// Salvar alterações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $intervalo = (int)$_POST['intervalo'];
    $email = $_POST['email'];

    $stmt = $pdo->prepare("UPDATE tbbackupconfig SET IntervaloHoras=?, EmailDestino=? WHERE IdConfig=?");
    $stmt->execute([$intervalo, $email, $backupConfig['IdConfig']]);

    header("Location: configBackup.php?msg=Configurações salvas com sucesso!");
    exit();
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $_SESSION['BASE_PATH'] . '/template/header.php'; ?>

    <style>
        .card-config {
            max-width: 650px;
            margin: 0 auto;
        }
    </style>
</head>

<script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/app.js"></script>

<body>
    <div class="wrapper">
        <?php require_once $_SESSION['BASE_PATH'] . '/template/menu.php'; ?>

        <div class="main">
            <?php require_once $_SESSION['BASE_PATH'] . '/template/topo.php'; ?>

            <main class="content">
                <div class="container-fluid p-0">

                    <h1 class="h3 mb-3">Configuração do Backup Automático</h1>

                    <!-- MENSAGENS -->
                    <?php if (isset($_GET['msg'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_GET['msg']); ?>
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
                                            <option value="<?= $i ?>"
                                                <?= ($backupConfig['IntervaloHoras'] == $i ? 'selected' : '') ?>>
                                                <?= $i ?> hora(s)
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">E-mail para envio do backup:</label>
                                    <input type="email"
                                        class="form-control"
                                        name="email"
                                        required
                                        value="<?php echo htmlspecialchars($backupConfig['EmailDestino']); ?>">
                                </div>

                                <button type="submit" class="btn btn-primary w-100">
                                    Salvar Configurações
                                </button>

                            </form>

                        </div>
                    </div>

                </div>
            </main>

            <footer class="footer">
                <?php require_once $_SESSION['BASE_PATH'] . '/template/footer.php'; ?>
            </footer>
        </div>
    </div>

</body>

</html>