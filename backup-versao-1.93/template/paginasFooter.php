<?php
session_set_cookie_params(['httponly' => true]);
session_start();
session_regenerate_id(true);
date_default_timezone_set('America/Sao_Paulo');

require_once $_SESSION['BASE_PATH'] . '/conectabd/conexao.php';

$page = $_GET['page'] ?? '';
$conteudo = '';

$config = $pdo->query("SELECT * FROM tbConfig LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Mapeamento entre 'page' e o campo do banco
$mapaPaginas = [
    'termos-de-uso' => 'TermosUso',
    'header-email' => 'HeaderEmail'
];

if (array_key_exists($page, $mapaPaginas)) {
    $campo = $mapaPaginas[$page];
    $conteudo = $config[$campo] ?? '<p>Conteúdo não disponível.</p>';
} else {
    $conteudo = '<p>Página não encontrada ou não configurada.</p>';
}
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
                    <?php echo $conteudo; ?>
                </div>
            </main>
            <footer class="footer">
                <?php require_once $_SESSION['BASE_PATH'] . '/template/footer.php' ?>
            </footer>
        </div>
    </div>

    <script src="<?php echo $_SESSION['BASE_URL']; ?>/assets/js/app.js"></script>
</body>

</html>