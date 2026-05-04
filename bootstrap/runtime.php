<?php
declare(strict_types=1);

if (!function_exists('bootstrap_runtime')) {
    function bootstrap_load_env_file(string $basePath): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $basePath = rtrim($basePath, '/\\');
        $envCandidates = [
            $basePath . DIRECTORY_SEPARATOR . '.env',
            $basePath . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . '.env',
            $basePath . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'invertexto.env',
        ];

        foreach ($envCandidates as $envPath) {
            if (!is_file($envPath) || !is_readable($envPath)) {
                continue;
            }

            $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (!is_array($lines)) {
                continue;
            }

            foreach ($lines as $line) {
                $line = trim((string)$line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$name, $value] = explode('=', $line, 2);
                $name = trim((string)$name);
                $value = trim((string)$value);
                if ($name === '') {
                    continue;
                }

                $len = strlen($value);
                if ($len >= 2 && (($value[0] === '"' && $value[$len - 1] === '"') || ($value[0] === "'" && $value[$len - 1] === "'"))) {
                    $value = substr($value, 1, -1);
                }

                if (getenv($name) === false) {
                    putenv($name . '=' . $value);
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }

    function bootstrap_env(string $name, string $default = ''): string
    {
        $value = getenv($name);
        if ($value !== false) {
            return trim((string)$value);
        }

        if (array_key_exists($name, $_ENV)) {
            return trim((string)$_ENV[$name]);
        }

        if (array_key_exists($name, $_SERVER)) {
            return trim((string)$_SERVER[$name]);
        }

        return $default;
    }

    function bootstrap_env_bool(string $name, bool $default = false): bool
    {
        $value = bootstrap_env($name, '');
        if ($value === '') {
            return $default;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $parsed ?? $default;
    }

    function bootstrap_is_production(): bool
    {
        $appEnv = strtolower(bootstrap_env('APP_ENV', ''));
        if ($appEnv !== '') {
            return in_array($appEnv, ['prod', 'production'], true);
        }

        $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        if ($host === '') {
            return false;
        }

        return !str_contains($host, 'localhost') && !str_starts_with($host, '127.0.0.1');
    }

    function bootstrap_is_debug(): bool
    {
        $appDebug = bootstrap_env('APP_DEBUG', '');
        if ($appDebug !== '') {
            return bootstrap_env_bool('APP_DEBUG', false);
        }

        return !bootstrap_is_production();
    }

    function bootstrap_apply_php_runtime(): void
    {
        $displayErrors = bootstrap_is_debug() ? '1' : '0';

        error_reporting(E_ALL);
        ini_set('display_errors', $displayErrors);
        ini_set('display_startup_errors', $displayErrors);
        ini_set('log_errors', '1');

        if (!bootstrap_is_debug()) {
            ini_set('html_errors', '0');
        }
    }

    function bootstrap_database_config(?string $basePath = null): array
    {
        if ($basePath !== null && $basePath !== '') {
            bootstrap_load_env_file($basePath);
        }

        return [
            'host' => bootstrap_env('DB_HOST', '127.0.0.1'),
            'port' => bootstrap_env('DB_PORT', '3306'),
            'name' => bootstrap_env('DB_NAME', ''),
            'user' => bootstrap_env('DB_USER', ''),
            'pass' => bootstrap_env('DB_PASS', ''),
        ];
    }

    function bootstrap_session_name(): string
    {
        return 'PHPSESSID3';
    }

    function bootstrap_auth_cookie_name(?string $basePath = null): string
    {
        if ($basePath !== null && $basePath !== '') {
            bootstrap_load_env_file($basePath);
        }

        $candidates = [
            bootstrap_env('AUTH_COOKIE_NAME', ''),
            bootstrap_env('LOGIN_COOKIE_NAME', ''),
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim((string)$candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        $resolvedBasePath = rtrim((string)($basePath ?? ''), '/\\');
        if ($resolvedBasePath !== '') {
            $legacyCookiePath = $resolvedBasePath . '/temp/setCookie.env';
            if (is_file($legacyCookiePath) && is_readable($legacyCookiePath)) {
                $rawCookieName = trim((string)file_get_contents($legacyCookiePath));
                if ($rawCookieName !== '') {
                    if (str_contains($rawCookieName, '=')) {
                        $parts = explode('=', $rawCookieName, 2);
                        $rawCookieName = trim((string)($parts[1] ?? ''));
                    }
                    if ($rawCookieName !== '') {
                        return $rawCookieName;
                    }
                }
            }
        }

        return 'login_v43';
    }

    function bootstrap_runtime(): array
    {
        $basePath = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
        bootstrap_load_env_file($basePath);
        bootstrap_apply_php_runtime();
        $projectSlug = '/' . basename($basePath);
        $sessionCookiePath = $projectSlug !== '' ? $projectSlug : '/';

        if (PHP_SAPI !== 'cli') {
            ini_set('default_charset', 'UTF-8');
            if (function_exists('mb_internal_encoding')) {
                mb_internal_encoding('UTF-8');
            }
            if (!headers_sent()) {
                header('Content-Type: text/html; charset=UTF-8');
            }
        }

        if (session_status() === PHP_SESSION_NONE && session_name() !== bootstrap_session_name()) {
            session_name(bootstrap_session_name());
        }

        $sessionActive = session_status() === PHP_SESSION_ACTIVE;
        if (PHP_SAPI !== 'cli' && !$sessionActive) {
            if (!headers_sent()) {
                session_set_cookie_params([
                    'path' => $sessionCookiePath,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
                @session_start();
                $sessionActive = session_status() === PHP_SESSION_ACTIVE;
            }
        }

        date_default_timezone_set('America/Sao_Paulo');

        $candidates = [
            (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH),
            (string)($_SERVER['SCRIPT_NAME'] ?? ''),
            (string)($_SERVER['PHP_SELF'] ?? ''),
            (string)($_SERVER['DOCUMENT_URI'] ?? ''),
        ];

        $baseUrl = '';
        foreach ($candidates as $candidate) {
            $candidate = str_replace('\\', '/', $candidate);
            $projectPos = strpos($candidate, $projectSlug);
            if ($projectPos !== false) {
                $baseUrl = substr($candidate, 0, $projectPos + strlen($projectSlug));
                break;
            }
        }

        if ($baseUrl === '') {
            $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
            $baseUrl = rtrim(dirname($scriptName), '/');
        }

        if ($baseUrl === '' || $baseUrl === '/') {
            $baseUrl = '/' . basename($basePath);
        }

        $baseUrl = rtrim($baseUrl, '/');

        $publicBaseUrl = bootstrap_env('APP_PUBLIC_BASE_URL', '');
        if ($publicBaseUrl === '') {
            $host = (string)($_SERVER['HTTP_HOST'] ?? '');
            if ($host !== '') {
                $isHttps = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
                $publicBaseUrl = ($isHttps ? 'https' : 'http') . '://' . $host . $baseUrl;
            } else {
                $publicBaseUrl = $baseUrl !== '' ? $baseUrl : '/conecta';
            }
        }
        $publicBaseUrl = rtrim($publicBaseUrl, '/');

        $sharedAssetsSlug = bootstrap_env('ASSETS_APP_SLUG', '/conectaosc');
        $sharedAssetsSlug = '/' . trim(str_replace('\\', '/', $sharedAssetsSlug), '/');
        if ($sharedAssetsSlug === '//') {
            $sharedAssetsSlug = '/conectaosc';
        }

        $currentHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        $publicOrigin = bootstrap_env('APP_PUBLIC_ORIGIN', '');
        if ($publicOrigin === '') {
            $publicOriginFromBase = bootstrap_env('APP_PUBLIC_BASE_URL', '');
            if ($publicOriginFromBase !== '') {
                $baseParts = parse_url($publicOriginFromBase);
                $baseScheme = (string)($baseParts['scheme'] ?? '');
                $baseHost = (string)($baseParts['host'] ?? '');
                $basePort = isset($baseParts['port']) ? (int)$baseParts['port'] : 0;
                if ($baseScheme !== '' && $baseHost !== '') {
                    $publicOrigin = $baseScheme . '://' . $baseHost . ($basePort > 0 ? ':' . $basePort : '');
                }
            }
        }
        if ($publicOrigin !== '' && !preg_match('#^https?://#i', $publicOrigin)) {
            $publicOrigin = 'https://' . ltrim($publicOrigin, '/');
        }
        $publicOrigin = rtrim($publicOrigin, '/');

        $remoteAssetsBaseUrl = bootstrap_env('ASSETS_BASE_URL', '');
        if ($remoteAssetsBaseUrl === '') {
            $isLocalHost = $currentHost === ''
                || str_starts_with($currentHost, 'localhost')
                || str_starts_with($currentHost, '127.0.0.1');
            if ($isLocalHost && $publicOrigin !== '') {
                $remoteAssetsBaseUrl = $publicOrigin . $sharedAssetsSlug;
            }
        }
        $remoteAssetsBaseUrl = rtrim($remoteAssetsBaseUrl, '/');

        $sharedAssetsBasePath = dirname($basePath) . DIRECTORY_SEPARATOR . trim($sharedAssetsSlug, '/');
        if (!is_dir($sharedAssetsBasePath)) {
            $sharedAssetsBasePath = $basePath;
        }

        $assetsCandidates = [
            $sharedAssetsBasePath . DIRECTORY_SEPARATOR . 'assets',
            $sharedAssetsBasePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'assets',
            $basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'assets',
            $basePath . DIRECTORY_SEPARATOR . 'assets',
        ];

        $assetsPath = '';
        foreach ($assetsCandidates as $candidate) {
            if (is_dir($candidate)) {
                $assetsPath = $candidate;
                break;
            }
        }
        if ($assetsPath === '') {
            $assetsPath = $assetsCandidates[0];
            @mkdir($assetsPath, 0777, true);
        }

        $assetsBaseUrl = $remoteAssetsBaseUrl !== '' ? $remoteAssetsBaseUrl : $sharedAssetsSlug;
        $assetsImgPath = rtrim($assetsPath, '/\\') . DIRECTORY_SEPARATOR . 'img';
        $assetsImgUrl = rtrim($assetsBaseUrl, '/') . '/assets/img';

        $runtimeSession = [];
        if ($sessionActive) {
            if (empty($_SESSION['BASE_para_PATH'])) {
                $_SESSION['BASE_para_PATH'] = $basePath;
            }
            if (!isset($_SESSION['BASE_para_URL']) || $_SESSION['BASE_para_URL'] === '' || $_SESSION['BASE_para_URL'] !== $baseUrl) {
                $_SESSION['BASE_para_URL'] = $baseUrl;
            }
            $_SESSION['BASE_ASSETS_PATH'] = $assetsPath;
            $_SESSION['BASE_ASSETS_URL'] = $assetsBaseUrl;
            $_SESSION['BASE_ASSETS_IMG_PATH'] = $assetsImgPath;
            $_SESSION['BASE_ASSETS_IMG_URL'] = $assetsImgUrl;
            $_SESSION['BASE_PUBLIC_URL'] = $publicBaseUrl;

            $runtimeSession = [
                'base_para_path' => (string)$_SESSION['BASE_para_PATH'],
                'base_para_url' => (string)$_SESSION['BASE_para_URL'],
            ];
        } else {
            $runtimeSession = [
                'base_para_path' => (string)$basePath,
                'base_para_url' => (string)$baseUrl,
            ];
        }

        return [
            'base_para_path' => (string)$runtimeSession['base_para_path'],
            'base_para_url' => (string)$runtimeSession['base_para_url'],
            'assets_path' => (string)$assetsPath,
            'assets_base_path' => (string)$sharedAssetsBasePath,
            'assets_base_url' => (string)$assetsBaseUrl,
            'assets_img_path' => (string)$assetsImgPath,
            'assets_img_url' => (string)$assetsImgUrl,
            'public_base_url' => (string)$publicBaseUrl,
        ];
    }

    function bootstrap_assets_img_url(): string
    {
        if (!empty($_SESSION['BASE_ASSETS_IMG_URL']) && is_string($_SESSION['BASE_ASSETS_IMG_URL'])) {
            return rtrim((string)$_SESSION['BASE_ASSETS_IMG_URL'], '/');
        }

        $runtime = bootstrap_runtime();
        return rtrim((string)($runtime['assets_img_url'] ?? ''), '/');
    }

    function bootstrap_assets_img_path(): string
    {
        if (!empty($_SESSION['BASE_ASSETS_IMG_PATH']) && is_string($_SESSION['BASE_ASSETS_IMG_PATH'])) {
            return rtrim((string)$_SESSION['BASE_ASSETS_IMG_PATH'], '/\\');
        }

        $runtime = bootstrap_runtime();
        return rtrim((string)($runtime['assets_img_path'] ?? ''), '/\\');
    }

    function bootstrap_resolve_assets_img_file(?string $assetRaw): string
    {
        $assetRaw = trim((string)$assetRaw);
        if ($assetRaw === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $assetRaw)) {
            return $assetRaw;
        }

        $assetRaw = str_replace('\\', '/', $assetRaw);
        $assetName = ltrim($assetRaw, '/');
        $runtime = bootstrap_runtime();
        $basePath = rtrim((string)($runtime['base_para_path'] ?? ''), '/\\');
        $assetsImgPath = bootstrap_assets_img_path();

        $candidates = [];
        if ($basePath !== '') {
            $candidates[] = $basePath . '/' . $assetName;
            $candidates[] = dirname($basePath) . '/' . $assetName;
            $candidates[] = $basePath . '/app/assets/img/' . $assetName;
            $candidates[] = $basePath . '/app/assets/img/logos/' . $assetName;
            $candidates[] = $basePath . '/app/assets/img/sistema/' . $assetName;
            $candidates[] = $basePath . '/app/assets/img/fotos/' . $assetName;
        }

        if ($assetsImgPath !== '') {
            $candidates[] = $assetsImgPath . '/' . $assetName;
            $candidates[] = $assetsImgPath . '/logos/' . $assetName;
            $candidates[] = $assetsImgPath . '/sistema/' . $assetName;
            $candidates[] = $assetsImgPath . '/fotos/' . $assetName;
        }

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return '';
    }

    function bootstrap_avatar_data_uri(string $label = 'Sem foto'): string
    {
        $label = trim($label);
        if ($label === '') {
            $label = 'Sem foto';
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96" role="img" aria-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">'
            . '<rect width="96" height="96" rx="48" fill="#e2e8f0"/>'
            . '<circle cx="48" cy="36" r="18" fill="#94a3b8"/>'
            . '<path d="M22 78c4-14 16-22 26-22s22 8 26 22" fill="#94a3b8"/>'
            . '</svg>';

        return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
    }

    function bootstrap_openai_api_key(?string $basePath = null): string
    {
        $runtime = bootstrap_runtime();
        $basePath = rtrim((string)($basePath ?: ($runtime['base_para_path'] ?? '')), '/\\');
        if ($basePath !== '') {
            $legacyKeyFile = $basePath . '/temp/openaikey.php';
            if (is_file($legacyKeyFile)) {
                $apiKey = '';
                require $legacyKeyFile;
                $apiKey = trim((string)($apiKey ?? ''));
                if (strlen($apiKey) >= 20) {
                    return $apiKey;
                }
            }
        }

        $candidates = [
            bootstrap_env('OPENAI_API_KEY', ''),
            bootstrap_env('API_OPENAI_KEY', ''),
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim((string)$candidate);
            if (strlen($candidate) >= 20) {
                return $candidate;
            }
        }

        return '';
    }

    function bootstrap_invertexto_api_token(?string $basePath = null): string
    {
        if ($basePath !== null && $basePath !== '') {
            bootstrap_load_env_file($basePath);
        }

        $candidates = [
            bootstrap_env('INVERTEXTO_API_TOKEN', ''),
            bootstrap_env('INVERTEXTO_TOKEN', ''),
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim((string)$candidate);
            if (strlen($candidate) >= 20) {
                return $candidate;
            }
        }

        return '';
    }

    function bootstrap_foto_url(?string $foto = null): string
    {
        $arquivo = trim((string)$foto);
        if ($arquivo !== '' && preg_match('/^https?:\/\//i', $arquivo)) {
            return $arquivo;
        }

        if ($arquivo !== '') {
            $resolved = bootstrap_resolve_assets_img_file($arquivo);
            if ($resolved !== '') {
                return bootstrap_assets_img_url() . '/fotos/' . rawurlencode(basename($arquivo));
            }
        }

        return bootstrap_avatar_data_uri();
    }
}

return bootstrap_runtime();
