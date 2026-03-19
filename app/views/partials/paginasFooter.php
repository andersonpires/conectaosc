<?php $runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = $runtime['base_para_url'];

require_once $BASE_para_PATH . '/api/conectabd/conexao.php';

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
    <?php require_once $BASE_para_PATH . '/app/views/partials/header.php'; ?>
</head>

<body>
    <div class="wrapper">
        <?php require_once $BASE_para_PATH . '/app/views/partials/menu.php'; ?>

        <div class="main">
            <?php require_once $BASE_para_PATH . '/app/views/partials/topo.php'; ?>
            <main class="content">
                <div class="container-fluid p-0">
                    <?php echo $conteudo; ?>
                </div>
            </main>
            <footer class="footer">
                <?php require_once $BASE_para_PATH . '/app/views/partials/footer.php' ?>
            </footer>
        </div>
    </div>

    <script src="<?php echo $BASE_para_URL; ?>/assets/js/app.js"></script>
</body>

</html>



