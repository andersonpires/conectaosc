<?php
$runtime = require __DIR__ . '/../bootstrap/runtime.php';
$BASE_para_URL = $runtime['base_url'];
header("Location: " . rtrim((string)$BASE_para_URL, '/') . "/assinatura/pdf/validar/");
exit;
