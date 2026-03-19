<?php
declare(strict_types=1);

$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_PATH = $runtime['base_para_path'];
$BASE_para_URL = rtrim((string)($runtime['base_para_url'] ?? ''), '/');
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Página não encontrada</title>
    <?php if (is_file($BASE_para_PATH . '/app/views/partials/header.php')): ?>
        <?php require $BASE_para_PATH . '/app/views/partials/header.php'; ?>
    <?php endif; ?>
</head>
<body>
    <div class="wrapper">
        <?php if (is_file($BASE_para_PATH . '/app/views/partials/menu.php')): ?>
            <?php require $BASE_para_PATH . '/app/views/partials/menu.php'; ?>
        <?php endif; ?>
        <div class="main">
            <?php if (is_file($BASE_para_PATH . '/app/views/partials/topo.php')): ?>
                <?php require $BASE_para_PATH . '/app/views/partials/topo.php'; ?>
            <?php endif; ?>
            <main class="content">
                <div class="container-fluid p-0">
                    <h1 class="h3 mb-3">Página não encontrada</h1>
                    <p>A rota solicitada não existe neste ambiente.</p>
                    <a class="btn btn-primary" href="<?php echo $BASE_para_URL; ?>/dashboard/">Voltar ao dashboard</a>
                </div>
            </main>
            <?php if (is_file($BASE_para_PATH . '/app/views/partials/footer.php')): ?>
                <footer class="footer"><?php require $BASE_para_PATH . '/app/views/partials/footer.php'; ?></footer>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
