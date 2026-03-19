<?php
declare(strict_types=1);

use FrontEnd\Core\FrontRouter;

$runtime = require __DIR__ . '/../../bootstrap/runtime.php';

$config = require __DIR__ . '/../config/app.php';
if (!empty($config['timezone'])) {
    date_default_timezone_set((string)$config['timezone']);
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'FrontEnd\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = __DIR__ . '/../src/' . str_replace('\\', '/', $relativeClass) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

$requestPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$basePath = dirname(__DIR__, 2);
$baseUrl = (string)($runtime['base_para_url'] ?? $runtime['base_url'] ?? '');
if ($baseUrl === '' || $baseUrl === '/') {
    $baseUrl = '/' . basename($basePath);
}
$projectDir = rtrim(str_replace('\\', '/', $baseUrl), '/');

if ($projectDir !== '' && str_starts_with($requestPath, $projectDir)) {
    $requestPath = substr($requestPath, strlen($projectDir));
}

$requestPath = $requestPath === '' ? '/' : $requestPath;
$baseUrl = $projectDir === '' ? '/' . basename($basePath) : $projectDir;

$router = new FrontRouter();
require __DIR__ . '/../routes/web.php';
$router->dispatch($requestPath);
