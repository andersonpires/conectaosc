<?php
namespace App\Middlewares;

use App\Core\JsonResponse;

class RateLimitMiddleware
{
    private static array $requests = [];
    private const MAX_REQUESTS = 60;
    private const WINDOW_SECONDS = 60;

    public static function check(): void
    {
        $key = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $now = time();
        if (!isset(self::$requests[$key])) {
            self::$requests[$key] = [];
        }
        self::$requests[$key] = array_filter(self::$requests[$key], fn($t) => $now - $t < self::WINDOW_SECONDS);
        if (count(self::$requests[$key]) >= self::MAX_REQUESTS) {
            JsonResponse::error('Muitas requisições. Tente novamente em breve.', [], 429);
        }
        self::$requests[$key][] = $now;
    }
}
