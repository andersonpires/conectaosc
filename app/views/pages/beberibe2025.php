<?php
$runtime = require __DIR__ . '/../../bootstrap/runtime.php';
$BASE_para_URL = rtrim((string)($runtime['base_para_url'] ?? ''), '/');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="<?php echo $BASE_para_URL; ?>/assets/img/landing/beberibe2025/icon-48x48.png" />
    <title>Fortalecimento do 3o Setor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="font-family:sans-serif;background:#f8f8f8;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px;background:linear-gradient(to bottom,#f8f8f8,#ffffff);overflow-x:hidden;">
    <div style="width:300px;margin-bottom:20px;overflow:hidden;display:flex;justify-content:center;">
        <img src="<?php echo $BASE_para_URL; ?>/assets/img/landing/beberibe2025/logo.png" alt="Logo" style="max-width:100%;height:auto;display:block;">
    </div>

    <div style="display:flex;flex-direction:column;width:100%;max-width:400px;">
        <a href="https://chat.whatsapp.com/KvOMx8WKXAd4gVfM30K9JG" class="btn btn-primary" style="background:#ff9800;border-color:#ff9800;margin-bottom:10px;width:100%;">Faca parte do grupo de WhatsApp AVISOS</a>
        <a href="https://chat.whatsapp.com/FBSrMoQBAVfKjz9ZokXIo2" class="btn btn-primary" style="background:#ff9800;border-color:#ff9800;margin-bottom:10px;width:100%;">Entrar para comunidade Fortalecimento 3o setor</a>
        <a href="https://1drv.ms/f/s!AmtY3_pDDneMmct2jrrbOVEEwtJm_g?e=jY2Ne4" class="btn btn-primary" style="background:#ff9800;border-color:#ff9800;margin-bottom:10px;width:100%;">Acessar arquivos no drive</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
