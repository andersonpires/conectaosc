<?php
declare(strict_types=1);

namespace BackEnd\Services;

use Throwable;

final class AuditLogService
{
    public function action(string $module, string $action, array $context = []): string
    {
        $traceId = $this->traceId();
        $this->write('INFO', $traceId, $module, $action, $context);
        return $traceId;
    }

    public function error(string $module, string $action, Throwable $e, array $context = []): string
    {
        $traceId = $this->traceId();
        $context['exception'] = [
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];
        $this->write('ERROR', $traceId, $module, $action, $context);
        return $traceId;
    }

    private function write(string $level, string $traceId, string $module, string $action, array $context): void
    {
        $basePath = dirname(__DIR__, 2);
        $dir = $basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $record = [
            'time' => date('c'),
            'level' => $level,
            'trace_id' => $traceId,
            'module' => $module,
            'action' => $action,
            'user_id' => (int) ($_SESSION['Cod'] ?? 0),
            'method' => (string) ($_SERVER['REQUEST_METHOD'] ?? ''),
            'uri' => (string) ($_SERVER['REQUEST_URI'] ?? ''),
            'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            'context' => $context,
        ];

        $line = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($line)) {
            return;
        }

        @file_put_contents($dir . DIRECTORY_SEPARATOR . 'api_audit.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function traceId(): string
    {
        try {
            return bin2hex(random_bytes(8));
        } catch (Throwable) {
            return substr(sha1((string) microtime(true)), 0, 16);
        }
    }
}

