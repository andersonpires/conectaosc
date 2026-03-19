<?php
declare(strict_types=1);

$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$basePath = (string) ($runtime['base_para_path'] ?? dirname(__DIR__, 3));
$baseUrl = (string) ($runtime['base_para_url'] ?? '');

require_once $basePath . '/app/controllers/ConfiguracaoFlow.php';

$flow = new \FrontEnd\Controllers\ConfiguracaoFlow($basePath, $baseUrl);
$flow->handle();

