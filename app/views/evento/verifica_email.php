<?php
declare(strict_types=1);

$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$basePath = (string) ($runtime['base_para_path'] ?? dirname(__DIR__, 3));

require_once $basePath . '/app/controllers/EventoVerificaEmailFlow.php';

$flow = new \FrontEnd\Controllers\EventoVerificaEmailFlow($basePath);
$flow->handle();

