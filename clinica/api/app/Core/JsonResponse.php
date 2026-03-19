<?php
namespace App\Core;

class JsonResponse
{
    public static function success($data = [], string $message = 'Operação realizada com sucesso', int $code = 200): void
    {
        self::send([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => [],
        ], $code);
    }

    public static function error(string $message, array $errors = [], int $code = 400): void
    {
        self::send([
            'success' => false,
            'message' => $message,
            'data' => (object)[],
            'errors' => $errors,
        ], $code);
    }

    private static function send(array $payload, int $code): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
