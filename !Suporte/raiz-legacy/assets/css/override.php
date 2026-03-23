<?php
$runtime = require dirname(__DIR__, 2) . '/bootstrap/runtime.php';
$basePath = $runtime['base_path'];
$corPrimaria = '#222E3C';
$corSecundaria = '#F5F7FB';

try {
    require_once $basePath . '/api/conectabd/conexao.php';
    if (isset($pdo)) {
        $config = $pdo->query("SELECT * FROM tbConfig LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $corPrimaria = $config['CorPrimaria'] ? $corPrimaria;
        $corSecundaria = $config['CorSecundaria'] ? $corSecundaria;
    }
} catch (Throwable $e) {
    // Fallback to defaults when config cannot be loaded
}

header("Content-Type: text/css");
?>

:root {
    --bs-primary: <?php echo htmlspecialchars($corPrimaria); ?> !important;
    --bs-secondary: <?php echo htmlspecialchars($corSecundaria); ?> !important;
}

/* ÐY"æ Cor prim�!­ria no menu */
.sidebar,
.sidebar-content,
.js-sidebar,
.js-simplebar {
    background-color: var(--bs-primary) !important;
}

.sidebar-item {
    background-color: var(--bs-primary) !important;
}

.sidebar-link {
    background-color: var(--bs-primary) !important;
}

.sidebar-item.active,
.sidebar-item.active > .sidebar-link {
    background-color: var(--bs-primary) !important;
    <!-- color: #fff !important; -->
}

.sidebar-header {
    <!-- color: #fff !important; -->
}

/* �s¦ Cor secund�!­ria no conte�!§do */
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
    background-color: white !important;
}
