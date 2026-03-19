<?php
$runtime = require dirname(__DIR__, 2) . '/bootstrap/runtime.php';
$basePath = $runtime['base_para_path'];
$corPrimaria = '#222E3C';
$corSecundaria = '#F5F7FB';

try {
    require_once $basePath . '/api/conectabd/conexao.php';
    if (isset($pdo)) {
        $config = $pdo->query("SELECT * FROM tbConfig LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $corPrimaria = $config['CorPrimaria'] ?? $corPrimaria;
        $corSecundaria = $config['CorSecundaria'] ?? $corSecundaria;
    }
} catch (Throwable $e) {
    // fallback
}

header('Content-Type: text/css; charset=UTF-8');
?>

:root {
    --bs-primary: <?php echo htmlspecialchars($corPrimaria, ENT_QUOTES, 'UTF-8'); ?> !important;
    --bs-secondary: <?php echo htmlspecialchars($corSecundaria, ENT_QUOTES, 'UTF-8'); ?> !important;
}

/* Remove folga superior global para manter a navbar no topo */
html,
body {
    margin: 0 !important;
    padding: 0 !important;
}

.wrapper,
.main,
.navbar,
.navbar-bg {
    margin-top: 0 !important;
    top: 0 !important;
}

/* Cor primaria no menu */
.sidebar,
.sidebar-content,
.js-sidebar,
.js-simplebar,
.sidebar-item,
.sidebar-link,
.sidebar-item.active,
.sidebar-item.active > .sidebar-link {
    background-color: var(--bs-primary) !important;
}

.sidebar-header {
    color: #fff !important;
}

/* Cor secundaria no conteudo */
.content,
.container-fluid {
    background-color: var(--bs-secondary) !important;
}

footer,
footer .col-6,
footer .text-start,
footer .text-end,
footer p,
footer a {
    background-color: #fff !important;
}
