<?php
declare(strict_types=1);

use FrontEnd\Core\FrontRouter;

$runtime = require __DIR__ . '/../bootstrap/runtime.php';

/**
 * Normaliza sequências de mojibake em páginas SSR.
 * Isso reduz impactos de arquivos legados com dupla codificação.
 */
if (!function_exists('frontend_fix_mojibake')) {
    function frontend_fix_mojibake(string $buffer): string
    {
        return strtr($buffer, [
            'Ã¡' => 'á', 'Ã¢' => 'â', 'Ã£' => 'ã', 'Ã¤' => 'ä',
            'Ã©' => 'é', 'Ãª' => 'ê', 'Ã­' => 'í', 'Ã³' => 'ó',
            'Ã´' => 'ô', 'Ãµ' => 'õ', 'Ãº' => 'ú', 'Ã§' => 'ç',
            'Ã' => 'Á', 'Ã‚' => 'Â', 'Ãƒ' => 'Ã', 'Ã„' => 'Ä',
            'Ã‰' => 'É', 'ÃŠ' => 'Ê', 'Ã' => 'Í', 'Ã“' => 'Ó',
            'Ã”' => 'Ô', 'Ã•' => 'Õ', 'Ãš' => 'Ú', 'Ã‡' => 'Ç',
            'â€“' => '–', 'â€”' => '—', 'â€œ' => '“', 'â€' => '”',
            'â€˜' => '‘', 'â€™' => '’', 'â€¦' => '…', 'Âº' => 'º',
            'Âª' => 'ª', 'Â°' => '°', 'Â·' => '·', 'Â' => '',
        ]);
    }
}

ob_start('frontend_fix_mojibake');

$config = require __DIR__ . '/config/app.php';
if (!empty($config['timezone'])) {
    date_default_timezone_set((string) $config['timezone']);
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'FrontEnd\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $relativePath = str_replace('\\', '/', $relativeClass) . '.php';
    $newFile = __DIR__ . '/' . strtolower(dirname($relativePath)) . '/' . basename($relativePath);
    $legacyFile = __DIR__ . '/src/' . $relativePath;

    if (is_file($newFile)) {
        require_once $newFile;
        return;
    }

    if (is_file($legacyFile)) {
        require_once $legacyFile;
    }
});

$requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$basePath = dirname(__DIR__);
$baseUrl = (string) ($runtime['base_para_url'] ?? $runtime['base_para_url'] ?? '');
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
require __DIR__ . '/routes/web.php';
$router->dispatch($requestPath);
