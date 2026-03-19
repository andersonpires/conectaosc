<?php
declare(strict_types=1);

namespace BackEnd\Controllers;

use BackEnd\Core\Response;
use BackEnd\Services\CursoService;
use Throwable;

final class CursoController
{
    public function __construct(private readonly CursoService $service)
    {
    }

    public function index(): void
    {
        Response::json([
            'success' => true,
            'message' => 'Cursos carregados',
            'data' => $this->service->list(),
            'errors' => [],
        ]);
    }

    public function projetos(): void
    {
        Response::json([
            'success' => true,
            'message' => 'Projetos carregados',
            'data' => $this->service->listProjetos(),
            'errors' => [],
        ]);
    }

    public function ativos(): void
    {
        Response::json([
            'success' => true,
            'message' => 'Cursos ativos carregados',
            'data' => $this->service->listAtivos(),
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        try {
            $payload = $this->requestPayload();
            $id = $this->service->create($payload);
            Response::json([
                'success' => true,
                'message' => 'Curso cadastrado com sucesso',
                'data' => ['id' => $id],
                'errors' => [],
            ], 201);
        } catch (Throwable $e) {
            Response::json([
                'success' => false,
                'message' => 'Falha ao cadastrar curso',
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
                'message' => $updated ? 'Curso atualizado com sucesso' : 'Curso sem alteração',
                'data' => ['updated' => $updated],
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            Response::json([
                'success' => false,
                'message' => 'Falha ao atualizar curso',
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
                'message' => $deleted ? 'Curso excluído com sucesso' : 'Curso não encontrado',
                'data' => ['deleted' => $deleted],
                'errors' => [],
            ]);
        } catch (Throwable $e) {
            Response::json([
                'success' => false,
                'message' => 'Falha ao excluir curso',
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

