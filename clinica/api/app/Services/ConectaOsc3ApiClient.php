<?php
namespace App\Services;

use RuntimeException;

class ConectaOsc3ApiClient
{
    private string $apiBaseUrl;

    public function __construct()
    {
        $this->apiBaseUrl = rtrim($this->resolveAppBaseUrl(), '/') . '/api/v1';
    }

    /**
     * @return array<string,mixed>
     */
    public function get(string $path, array $query = []): array
    {
        $url = $this->apiBaseUrl . '/' . ltrim($path, '/');
        if (!empty($query)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Cookie: ' . $this->buildCookieHeader(),
        ];

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if (!is_string($response) || trim($response) === '') {
            throw new RuntimeException('Falha ao consumir API do ConectaOSC3: resposta vazia');
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Falha ao consumir API do ConectaOSC3: resposta invalida');
        }

        $success = (bool)($decoded['success'] ?? false);
        if (!$success) {
            $message = (string)($decoded['message'] ?? 'Falha ao consumir API do ConectaOSC3');
            throw new RuntimeException($message);
        }

        return $decoded;
    }

    private function resolveAppBaseUrl(): string
    {
        $sessionBase =
            (string)($_SESSION['BASE_para_URL'] ?? '') !== ''
            ? (string)$_SESSION['BASE_para_URL']
            : '';

        if ($sessionBase !== '') {
            return rtrim($sessionBase, '/');
        }

        $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

        $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/clinica/api/index.php'));
        $clinicaPrefix = '/clinica/api';
        $pos = strpos($scriptName, $clinicaPrefix);
        $projectBasePath = $pos === false ? '' : rtrim(substr($scriptName, 0, $pos), '/');

        return ($isHttps ? 'https' : 'http') . '://' . $host . $projectBasePath;
    }

    private function buildCookieHeader(): string
    {
        $pairs = [];
        foreach ($_COOKIE as $name => $value) {
            if (!is_string($name) || $name === '') {
                continue;
            }
            if (!is_scalar($value)) {
                continue;
            }
            $pairs[] = rawurlencode($name) . '=' . rawurlencode((string)$value);
        }

        $sessionName = session_name();
        $sessionId = session_id();
        if ($sessionName !== '' && $sessionId !== '') {
            $needle = rawurlencode($sessionName) . '=';
            $exists = false;
            foreach ($pairs as $pair) {
                if (str_starts_with($pair, $needle)) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $pairs[] = rawurlencode($sessionName) . '=' . rawurlencode($sessionId);
            }
        }

        return implode('; ', $pairs);
    }
}
