<?php
declare(strict_types=1);

namespace BackEnd\Controllers;

use BackEnd\Core\Response;
use BackEnd\Services\ConfigService;
use Throwable;

final class ConfigController
{
    public function __construct(private readonly ConfigService $service)
    {
    }

    public function index(): void
    {
        $config = $this->service->getConfig();

        Response::json([
            'success' => true,
            'message' => 'Configurações carregadas',
            'data' => $config,
            'errors' => [],
        ]);
    }

    public function update(): void
    {
        try {
            $payload = $this->readJsonBody();
            $config = $this->service->updateConfig($payload);

            Response::json([
                'success' => true,
                'message' => 'Configurações atualizadas com sucesso',
                'data' => $config,
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            Response::json([
                'success' => false,
                'message' => 'Falha ao atualizar configurações',
                'data' => (object) [],
                'errors' => [$e->getMessage()],
            ], 422);
        }
    }

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || trim($raw) === '') {
            return $_POST ?? [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return $_POST ?? [];
    }
}

