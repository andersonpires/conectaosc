<?php
session_set_cookie_params(['httponly' => true]);
ini_set('session.gc_maxlifetime', 86400);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['BASE_PATH']) || !isset($_SESSION['BASE_URL'])) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location:" . dirname($_SERVER['SERVER_NAME']) . "/../../login.php?redirect=$redirect_url");
    exit();
}

$resultado = isset($_SESSION['resultado_matriculas_lote']) ? $_SESSION['resultado_matriculas_lote'] : null;
unset($_SESSION['resultado_matriculas_lote']);

if (!$resultado || !isset($resultado['linhas']) || !is_array($resultado['linhas'])) {
    header("Location: " . $_SESSION['BASE_URL'] . "/app/beneficiario/listagemSBenef.php?erro=" . urlencode("Nenhum resultado de matricula para exibir.") . "&matricula=1");
    exit();
}

$urlRetorno = $_SESSION['BASE_URL'] . "/app/beneficiario/listagemSBenef.php?msg=%27Selecione%20as%20pessoas%20e%20clique%20em%20Matricular%20Aluno%27&matricula=1";
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php require_once $_SESSION['BASE_PATH'] . '/template/header.php'; ?>
</head>

<body>
    <div class="wrapper">
        <?php require_once $_SESSION['BASE_PATH'] . '/template/menu.php'; ?>
        <div class="main">
            <?php require_once $_SESSION['BASE_PATH'] . '/template/topo.php'; ?>

            <main class="content">
                <div class="container-fluid p-0">
                    <h1 class="h3 mb-3">Resultado das matriculas</h1>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Detalhes do processamento</h5>
                            <small class="text-muted">Executado em: <?php echo htmlspecialchars($resultado['data_execucao'] ?? date('d/m/Y H:i:s')); ?></small>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Beneficiario(a)</th>
                                            <th>Cursos/Turmas matriculados</th>
                                            <th>Observacoes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($resultado['linhas'] as $linha) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($linha['aluno'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($linha['matriculas'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($linha['observacoes'] ?? ''); ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <a href="<?php echo $urlRetorno; ?>" class="btn btn-primary mt-3">Retornar</a>
                </div>
            </main>

            <footer class="footer">
                <?php require_once $_SESSION['BASE_PATH'] . '/template/footer.php'; ?>
            </footer>
        </div>
    </div>

    <script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/app.js"></script>
</body>

</html>