<?php
declare(strict_types=1);

$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$basePath = (string) ($runtime['base_para_path'] ?? dirname(__DIR__, 3));
$baseUrl = (string) ($runtime['base_para_url'] ?? '');

require_once $basePath . '/app/controllers/EventoExcluirFlow.php';

$flow = new \FrontEnd\Controllers\EventoExcluirFlow($basePath, $baseUrl);
$flow->handle();

