<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_URL = $runtime['base_para_url'];
$validarUrl = rtrim((string)$BASE_para_URL, '/') . '/assinatura/pdf/validar/';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Abrindo validação</title>
</head>
<body>
    <p>Abrindo validação em nova aba...</p>
    <p><a href="<?= htmlspecialchars($validarUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Abrir validação</a></p>
    <script>
        (function () {
            const url = <?= json_encode($validarUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            window.open(url, '_blank', 'noopener');

            // Mantém o PDF aberto na aba atual, voltando para a página anterior.
            setTimeout(function () {
                if (window.history.length > 1) {
                    window.history.back();
                    return;
                }
                window.location.href = <?= json_encode(rtrim((string)$BASE_para_URL, '/'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?> + '/';
            }, 120);
        })();
    </script>
    <noscript>
        <meta http-equiv="refresh" content="0;url=<?= htmlspecialchars($validarUrl, ENT_QUOTES, 'UTF-8') ?>">
    </noscript>
</body>
</html>
