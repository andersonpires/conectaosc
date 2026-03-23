<?php
declare(strict_types=1);

namespace BackEnd\Controllers;

use BackEnd\Core\Response;
use BackEnd\Services\MatriculaService;
use Throwable;

final class MatriculaController
{
    public function __construct(private readonly MatriculaService $service)
    {
    }

    public function storeLote(): void
    {
        try {
            $result = $this->service->processarLote($this->requestPayload());
            if (($result['ok'] ?? false) !== true) {
                Response::json([
                    'success' => false,
                    'message' => 'Falha ao processar matrículas',
                    'data' => (object) [],
                    'errors' => [$result['erro'] ?? 'Erro desconhecido'],
                ], 422);
            }

            Response::json([
                'success' => true,
                'message' => 'Matrículas processadas',
                'data' => $result['resultado'],
                'errors' => [],
            ], 201);
        } catch (Throwable $e) {
            Response::json([
                'success' => false,
                'message' => 'Erro ao processar matrículas',
                'data' => (object) [],
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    public function update(string $id): void
    {
        try {
            $payload = $this->requestPayload();
            $payload['idmatricula'] = (int)$id;
            $updated = $this->service->update($payload);

            Response::json([
                'success' => true,
                'message' => $updated ? 'Matrícula atualizada com sucesso' : 'Matrícula sem alteração',
                'data' => ['updated' => $updated],
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            Response::json([
                'success' => false,
                'message' => 'Erro ao atualizar matrícula',
                'data' => (object) [],
                'errors' => [$e->getMessage()],
            ], 422);
        }
    }

    public function porTurma(): void
    {
        $idTurma = isset($_GET['turma']) ? (int)$_GET['turma'] : 0;
        $ativo = isset($_GET['ativo']) ? (int)$_GET['ativo'] : 1;
        if ($idTurma <= 0) {
            Response::json([
                'success' => false,
                'message' => 'Turma inválida',
                'data' => [],
                'errors' => ['Parâmetro turma é obrigatório'],
            ], 422);
        }

        $data = $this->service->listByTurma($idTurma, $ativo === 1);
        Response::json([
            'success' => true,
            'message' => 'Matrículas carregadas',
            'data' => $data,
            'errors' => [],
        ]);
    }

    public function ativar(string $id): void
    {
        try {
            $ok = $this->service->ativar((int)$id, (int)($_SESSION['Cod'] ?? 0));
            Response::json([
                'success' => true,
                'message' => $ok ? 'Matrícula ativada com sucesso' : 'Matrícula sem alteração',
                'data' => ['updated' => $ok],
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            Response::json([
                'success' => false,
                'message' => 'Erro ao ativar matrícula',
                'data' => (object) [],
                'errors' => [$e->getMessage()],
            ], 422);
        }
    }

    public function destroy(string $id): void
    {
        try {
            $payload = $this->requestPayload();
            $payload['idmatricula'] = (int)$id;
            $deleted = $this->service->softDelete($payload, (int)($_SESSION['Cod'] ?? 0));

            Response::json([
                'success' => true,
                'message' => $deleted ? 'Matrícula inativada com sucesso' : 'Matrícula sem alteração',
                'data' => ['deleted' => $deleted],
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            Response::json([
                'success' => false,
                'message' => 'Erro ao inativar matrícula',
                'data' => (object) [],
                'errors' => [$e->getMessage()],
            ], 422);
        }
    }

    private function requestPayload(): array
    {
        $raw = file_get_contents('php://input');
        $json = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($json)) {
            return $json;
        }

        return $_POST ?? [];
    }
}

