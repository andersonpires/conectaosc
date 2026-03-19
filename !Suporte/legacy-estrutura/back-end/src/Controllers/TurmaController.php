<?php
declare(strict_types=1);

namespace BackEnd\Controllers;

use BackEnd\Core\Response;
use BackEnd\Services\TurmaService;
use Throwable;

final class TurmaController
{
    public function __construct(private readonly TurmaService $service)
    {
    }

    public function index(): void
    {
        Response::json([
            'success' => true,
            'message' => 'Turmas carregadas',
            'data' => $this->service->list(),
            'errors' => [],
        ]);
    }

    public function porCurso(): void
    {
        $idCurso = isset($_GET['IdCurso']) ? (int)$_GET['IdCurso'] : 0;
        $somenteAtivos = isset($_GET['somenteAtivos']) ? (int)$_GET['somenteAtivos'] : 1;
        if ($idCurso <= 0) {
            Response::json([
                'success' => false,
                'message' => 'IdCurso inválido',
                'data' => [],
                'errors' => ['IdCurso obrigatório'],
            ], 422);
        }

        Response::json([
            'success' => true,
            'message' => 'Turmas carregadas',
            'data' => $this->service->listByCurso($idCurso, $somenteAtivos),
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        try {
            $count = $this->service->create($this->requestPayload());
            Response::json([
                'success' => true,
                'message' => $count . ' turma(s) cadastrada(s) com sucesso',
                'data' => ['count' => $count],
                'errors' => [],
            ], 201);
        } catch (Throwable $e) {
            Response::json([
                'success' => false,
                'message' => 'Falha ao cadastrar turmas',
                'data' => (object) [],
                'errors' => [$e->getMessage()],
            ], 422);
        }
    }

    public function update(string $id): void
    {
        try {
            $updated = $this->service->update((int) $id, $this->requestPayload());
            Response::json([
                'success' => true,
                'message' => $updated ? 'Turma atualizada com sucesso' : 'Turma sem alteração',
                'data' => ['updated' => $updated],
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            Response::json([
                'success' => false,
                'message' => 'Falha ao atualizar turma',
                'data' => (object) [],
                'errors' => [$e->getMessage()],
            ], 422);
        }
    }

    public function destroy(string $id): void
    {
        try {
            $deleted = $this->service->delete((int) $id);
            Response::json([
                'success' => true,
                'message' => $deleted ? 'Turma excluída com sucesso' : 'Turma não encontrada',
                'data' => ['deleted' => $deleted],
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            Response::json([
                'success' => false,
                'message' => 'Falha ao excluir turma',
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
