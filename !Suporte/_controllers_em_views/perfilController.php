<?php
declare(strict_types=1);

$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$basePath = (string) ($runtime['base_path'] ?? dirname(__DIR__, 3));
$baseUrl = (string) ($runtime['base_url'] ?? '');

require_once $basePath . '/app/controllers/PerfilFlow.php';

$flow = new \FrontEnd\Controllers\PerfilFlow($basePath, $baseUrl);
$flow->handle();

