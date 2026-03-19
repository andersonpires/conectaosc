<?php
$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$baseUrl = rtrim((string)($runtime['base_para_url'] ?? ''), '/');
header('Location: ' . $baseUrl . '/projetos/');
exit;
