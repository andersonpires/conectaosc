<?php
declare(strict_types=1);

$runtime = require __DIR__ . '/../../../bootstrap/runtime.php';
$basePath = (string) ($runtime['base_path'] ?? dirname(__DIR__, 3));
$baseUrl = (string) ($runtime['base_url'] ?? '');

require_once $basePath . '/app/controllers/CrmActionFlow.php';

$flow = new \FrontEnd\Controllers\CrmActionFlow($basePath, $baseUrl);
$flow->handle();

