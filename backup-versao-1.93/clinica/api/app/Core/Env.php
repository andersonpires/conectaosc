<?php
/**
 * App Clínica - Auto-detecção de ambiente
 */

namespace App\Core;

class Env
{
    private static ?array $config = null;

    public static function init(): array
    {
        if (self::$config !== null) {
            return self::$config;
        }

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $isDev = (stripos($host, 'localhost') !== false || stripos($host, '127.0.0.1') !== false);
        $env = $isDev ? 'development' : 'production';

        $basePath = '/conectaosc';
        $baseUrl = $isDev
            ? 'http://' . $host . $basePath
            : 'https://' . $host . $basePath;

        self::$config = [
            'APP_ENV' => $env,
            'APP_BASE_PATH' => $basePath,
            'CLINICA_BASE_PATH' => $basePath . '/clinica',
            'APP_BASE_URL' => $baseUrl,
            'FRONT_BASE_URL' => $baseUrl . '/clinica',
            'API_BASE_URL' => $baseUrl . '/clinica/api',
            'IS_PRODUCTION' => ($env === 'production'),
        ];

        return self::$config;
    }

    public static function get(string $key, $default = null)
    {
        if (self::$config === null) {
            self::init();
        }
        return self::$config[$key] ?? $default;
    }
}
