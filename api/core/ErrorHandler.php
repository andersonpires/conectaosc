<?php
declare(strict_types=1);

namespace BackEnd\Core;

final class ErrorHandler
{
    public static function register(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
        date_default_timezone_set('America/Sao_Paulo');

        set_exception_handler(static function (\Throwable $exception): void {
            Response::json(
                [
                    'success' => false,
                    'message' => 'Internal server error',
                    'data' => (object) [],
                    'errors' => [self::exceptionToString($exception)],
                ],
                500
            );
        });

        register_shutdown_function(static function (): void {
            $error = error_get_last();
            if ($error === null) {
                return;
            }

            $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
            if (!in_array($error['type'], $fatalTypes, true)) {
                return;
            }

            Response::json(
                [
                    'success' => false,
                    'message' => 'Fatal server error',
                    'data' => (object) [],
                    'errors' => [$error['message'] . ' at ' . $error['file'] . ':' . $error['line']],
                ],
                500
            );
        });
    }

    private static function exceptionToString(\Throwable $exception): string
    {
        return $exception->getMessage() . ' at ' . $exception->getFile() . ':' . $exception->getLine();
    }
}

